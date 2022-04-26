#!/usr/bin/env perl

## SmartSpice test system
## (C) 2012 SILVACO Inc.
## Author: Sergey Oleynik
## Edited: Pavel Huryi

use strict;
use English;
use IO::Socket;
use Win32::Process;
use Win32::Process qw/STILL_ACTIVE/;
use Win32;
use Win32::API;
use Win32::GuiTest qw/:ALL/;
use Getopt::Std;
use Cwd 'abs_path';
use File::Path;
use File::Copy;
use File::Basename;
use File::Find;
use Fcntl;
use POSIX ":sys_wait_h";

# Flush STDOUT/STDERR filehandles after each print/write statement. 
$| = 1; 
Win32::API::->Import("user32", "DWORD GetWindowThreadProcessId ( HWND hwnd, LPDWORD lpdwProcessId)") or die $^E;

###########
# Globals #
###########
my $QADB_UPLOAD = 0;
my $ScriptFile;                 # Script file name 
my $ScriptPath;                 # Script path
my $LocalHost;                  # Local host name
my $UserName;                   # User name
my $SystemName;                 # System name
my $Home;                       # /home path
my $Build;                      # /build path
my $Temp;                       # Path for temporary files

my $Parallel = 0;               # Number of processes to run in parallel
my $ProcessId = -1;             # ProcessId when run in parallel mode

my $StartTime;                  # Time script started
my $EndTime;                    # Time script finished
my $TotalTimer = 0;             # Total start/end time timer
my $Timer = 0;                  # Timer

my $InstalledSmartSpice;        # Installed smartspice path suffix
my $SmartSpice;                 # SmartSpice executable
my $SmartSpiceFlags = "-P 1 -PS 1"; # Flags for SmartSpice
my $BatchMode = "-b";           # Flag to use for batchmode/silent batchmode

my $TestsPath;                  # Path with tests
my $TestsToRun = undef;         # If specified, tests dir to run
my $OutPath;                    # Output path
my $InfoFile;                   # File which will contain info data
my $StatusFile;
my $ResultsFile;                # Diffs results file
my $TMIResultFile;              # TMI evaluation script output file

my $RunDiffs = 1;               # Flag telling wether to run diff script or not
my $RefTestsRepository;         # Path to reference tests repository
my $RefTestsVersion;            # Reference tests version to compare with
my $RefTestsPath;               # Path to reference tests results to compare with

my $IgnoreDirsINI;              # Ignore-dirs ini file
my @IgnoreDirs = ();            # Directories with tests to skip
my $InOrderINI;                 # In-order dirs ini file
my @InOrderDirs = ();           # In-order dirs

my @InOrderDecks = ();          # List of input decks that should be run in order  
my @ParallelDecks = ();         # List of input decks allowable to be run in parallel  
my $TotalDecks;                 # Total amount of decks that will be run

my $OverrideSFLM = 1;           # Flag telling wether to override SFLM or not
my $OverrideSFLMnextMonth = 0;     # Flag telling to test  override SFLM for next month
my $SFLMoverridesPath;          # Directory with overrides for SFLM
my $SFLMoverride = undef;       # Current SFLM override

my $SolversTestMode = 0;        # Flag to turn on solvers test mode
my $DailyTestMode   = 0;        # Flag to turn on daily testing mode

my $RunTMIscript = 0;           # Flag to control processing of TMI examples with TSMC script
my @TMIdirs = ();               # TMI packages directories

## Added by Arnold
my $port_init = 12345; # change this at will
my $port_max = 12347;
my $port = $port_init;
##############
# Main block #
##############

# Setup script filename and path

$_ = $0;
/([\w]+)\.pl$/;
$ScriptFile = $&;
$ScriptPath = $`;
if (!$ScriptPath)
{
  $ScriptPath = ".";
}
$ScriptPath = abs_path($ScriptPath);
$ScriptPath =~ s/\\/\//g;
$ScriptPath =~ s/[\/]$//;

# Get start time

my ($sec, $min, $hour, $mday, $mon, $year, $wday, $yday, $isdst) = localtime(time);
$StartTime = sprintf("%s %s %02d %02d:%02d:%02d %4d", 
                    ("Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat")[$wday],
                    ("Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec")[$mon],
                    $mday, $hour, $min, $sec, 1900+$year);
$TotalTimer = time();

print "\nSmartSpice Test System\n";
print   "----------------------\n";

# Get platform data
GetPlatformData();

# Set default ignore dirs file. See option '-g <ignoredirs_file_path>'
$IgnoreDirsINI = "$Home/SSTestIgnoreDirs.ini";

# Find latest available reference test results version
FindRefTestsVersion();

# Get commandline options
my %options = ();
if (!getopts('c:w:e:f:hi:mno:p:r:g:stx:zdTl:u', \%options)) { PrintHelp(); }
$TestsToRun = shift;
if ($options{w}) 
{
    my $command = "perl $ScriptPath/TSTest.pl -g $options{w} -e $options{i} -o $options{o}";
      if (system($command) != 0)
      {
        PrintInfo("Error: Failed to launch testspice script\n");
      }
      exit;
 }
if ($options{h}) { PrintHelp(); }
if ($options{f}) { $SmartSpiceFlags = $options{f}; }
if ($options{m}) { $OverrideSFLM = 0; }
if ($options{n}) { $OverrideSFLMnextMonth = 1; }
if ($options{o}) { $OutPath = $options{o}; }
if ($options{s}) { $BatchMode = "-sb"; }
if ($options{t}) { $RunDiffs = 0; }
if ($options{z}) { $SolversTestMode = 1; $RunDiffs = 0; }
if ($options{d}) { $DailyTestMode = 1; }
if ($options{g}) { $IgnoreDirsINI = $options{g}; }
if ($options{p}) 
{
   $Parallel = $options{p};
   if ($Parallel <= 1) { $Parallel = 0; }
   if ($Parallel > 10) { $Parallel = 10; }
}
if ($options{e}) { $SmartSpice = $options{e}; }
if ($options{i}) { $SmartSpice = "/main/alpha/lib/smartspice/" . $options{i} . "/$InstalledSmartSpice"; }
if ($options{x}) { $SmartSpice = $options{x}; }
#else
#{
#  if (!(-e $SmartSpice)) { die "\nError: SmartSpice executable not found.\n[$SmartSpice]\n\n"; }
#  if (!(-f $SmartSpice)) { die "\nError: SmartSpice executable is not a file.\n[$SmartSpice]\n\n"; }
#}
if ($options{c})
{
  $TestsPath = $options{c};
  if (!(-e $TestsPath)) { die "\nError: Tests path not found\n[$TestsPath]\n\n"; }
  if (!(-d $TestsPath)) { die "\nError: Tests path is not a directory.\n[$TestsPath]\n\n"; }
  $TestsPath =~ s/\/$//o;
  $TestsPath = abs_path($TestsPath);
}
if ($options{r})
{
  $RefTestsPath = $options{r};
  if (!(-e $RefTestsPath)) { die "\nError: Reference tests path not found\n[$RefTestsPath]\n\n"; }
  if (!(-d $RefTestsPath)) { die "\nError: Reference tests repository is not a directory.\n[$RefTestsPath]\n\n"; }
  $RefTestsPath =~ s/\/$//o;
  $RefTestsPath = abs_path($RefTestsPath);
}
if ($options{T}) { $RunTMIscript = 1; }

#
# Set SFLM override path
#
if ($options{l}) {   $SFLMoverridesPath = $options{l}; }
if ($options{u}) { $QADB_UPLOAD = 1;}
if ($TestsToRun)
{
  $TestsToRun =~ s/^\///o; # Chop head slash if present
  $TestsToRun =~ s/\/$//o; # Chop trailing slash if present
  $RefTestsPath = "$RefTestsPath/$TestsToRun";
  $TestsPath = "$TestsPath/$TestsToRun";
}
if (!(-e $TestsPath)) { die "\nError: Tests path not found\n[$TestsPath]\n\n"; }
if ($RunDiffs && !(-e $RefTestsPath)) { die "\nError: Reference tests path not found\n[$RefTestsPath]\n\n"; }

# Generate output path
{
  my ($date, $i);
  if (!(-e $OutPath)) { File::Path::mkpath($OutPath, 0, 0755) or die "\nError: Unable to create output path\n[$OutPath]\n\n"; }
  $OutPath = abs_path($OutPath);
  if ( !$DailyTestMode ) {
    $date = sprintf("%02d%02d", $mon+1, $mday);
    $OutPath = "$OutPath/$SystemName-$date";
    for ($i = 0; ; $i++) { last unless (-d $OutPath . "-$i" ); }
    $OutPath .= "-$i";
  }
  # Create outpath along with dir for temp files
  $Temp = "$OutPath/.temp";
  File::Path::mkpath("$Temp", 0, 0755) or die "\nError: Unable to create output path\n[$Temp]\n\n";
}
$InfoFile = "$OutPath.info";
$StatusFile = "$OutPath.st";
$ResultsFile = "$OutPath.results";
$TMIResultFile = "$OutPath.tmi";

# Set LD_LIBRARY_PATH (not needed if invocation via script launcher is supplied)
if (!$options{x})
{
  my $LDpath = $SmartSpice;
  $LDpath =~ s/\/[\w.]*$/\//o;
  $ENV{"LD_LIBRARY_PATH"} = "$LDpath:./:$ENV{'LD_LIBRARY_PATH'}";
}

# Print info.
open(INFO, ">$InfoFile");
PrintInfo("Started    : $StartTime\n");
PrintInfo("Localhost  : $LocalHost\n");
PrintInfo("Username   : $UserName\n");
PrintInfo("System     : $SystemName\n");
PrintInfo("SmartSpice : $SmartSpice\n");
PrintInfo("SmartSpice flags  : $BatchMode $SmartSpiceFlags\n");
PrintInfo("Tests to run      : $TestsPath\n");
if ($RunDiffs) { PrintInfo("Reference tests   : $RefTestsPath\n"); }
PrintInfo("Output path       : $OutPath\n");
if ($RunDiffs) { PrintInfo("Diff results file : $ResultsFile\n"); }
PrintInfo("\n");

if ($SolversTestMode) { PrintInfo("Solvers test mode.\n\n"); }

# Read INI files.
PrintInfo("INI files:\n");
ReadInOrderINIFile();
ReadIgnoreDirsINIFile();
PrintInfo("\n");

# Set SFLM override
if ($OverrideSFLM)
{
  my $month = sprintf "\\.%02d", $mon + 1 + $OverrideSFLMnextMonth;
  $SFLMoverride = `ls -A $SFLMoverridesPath | grep "$month" | grep -v "sol"`;
  if ($SFLMoverride =~ /^\./)
  {
    $SFLMoverride =~ s/\s+$//g;
    $ENV{"SFLM_OVERRIDE"} = $SFLMoverride;
    $ENV{"SFLM_FLEXLM"} = 0;
    PrintInfo("Forcing SFLM_OVERRIDE=$SFLMoverride\n");
    PrintInfo("Forcing SFLM_FLEXLM=0\n\n");
  }
  else
  {
    $SFLMoverride = undef;
  }
}
if (!$SFLMoverride)
{
  if ($ENV{"SFLM_OVERRIDE"}) { delete $ENV{"SFLM_OVERRIDE"}; }
  PrintInfo("SFLM active.\n\n");

#
# Use SFLM_FLEXLM
#
#  $ENV{"SFLM_FLEXLM"} = 1;
#  $ENV{"SIMUCAD_LICENSE_FILE"} = "27000\@sflmhost";
#  PrintInfo("Using SFLM_FLEXLM=$ENV{'SFLM_FLEXLM'}\n");
#  PrintInfo("Using SIMUCAD_LICENSE_FILE=$ENV{'SIMUCAD_LICENSE_FILE'}\n\n");
}

# Display InOrder Dirs and Ignored Decks info
if ($#InOrderDirs != -1) {
  my $sep = $";
  $" = "\n";
  PrintInfo("Forcing InOrder directories:\n@InOrderDirs\n\n");
  $" = $sep;
}
if ($#IgnoreDirs != -1) {
  my $sep = $";
  $" = "\n";
  PrintInfo("Ignoring directories:\n@IgnoreDirs\n\n");
  $" = $sep;
}

# Set up EACSCRIPT_PATH environment variable
$ENV{"EACSCRIPT_PATH"} = "$ScriptPath/EACscript/EACconvert.pl";
my ($cmd_name , $cmd_ver , $cmd_platform , $cmd_exe) = get_pv();
$cmd_platform = $SystemName unless ($cmd_platform);


# Prepare tests.
$Timer = time();
PrepareTests();
$Timer = time() - $Timer;

print INFO "Total decks: $TotalDecks\n";
PrintInfo("Time to prepare decks: " . StoHMS($Timer) . " [$Timer second(s)]\n\n");
close(INFO);

# Run decks.
##arnold 
my $sql_idx = add_idx($SmartSpice , $cmd_ver , $cmd_platform , $TestsPath);

$Timer = time();
RunDecks();
$Timer = time() - $Timer;

open(INFO, ">>$InfoFile");
PrintInfo("\nTime to run decks: " . StoHMS($Timer) . " [$Timer second(s)]\n");

# Run TMI evaluation scripts
if ($RunTMIscript)
{
  $Timer = time();
  print "\n\nStart TMI script...\n";

  for (my $i = 0; $i < $#TMIdirs + 1; $i++)
  {
  	  my $command = "perl ${ScriptPath}/SSTestTMI.pl \"${SmartSpice}\" \"${TMIdirs[$i]}\" | tee $TMIResultFile 2>&1";
	 
	  if (system($command) != 0)
	  {
		PrintInfo("\nError: Failed to launch TMI evaluation script\n");
	  }
  }
  
  $Timer = time() - $Timer;
  PrintInfo("\nTime to run TMI evaluation script: " . StoHMS($Timer) . " [$Timer second(s)]\n");
}
else
{
  PrintInfo("\nSkipping TMI evaluation.\n");
}

# Check if core was dumped.
print "\nSearching output directory for core dump files...\n";
my $err = `find $OutPath -name "core*"`;
if ($err)
{
  PrintInfo("Error: Core dump(s) found:\n$err\n");
}

print "\nSearching output directory for segmentation message ...\n";
my $err = `find $OutPath -type f -name "*.out" -o -name "*.err" -o -name "*.lis" | xargs grep "Internal Error" *`;
if ($err)
{
  PrintInfo("Error: Segmentations dump(s) found:\n$err\n");
}

# Run diff script.
if ($RunDiffs)
{
  my $command = "perl $ScriptPath/SSTestDiff.pl $RefTestsPath $OutPath > $ResultsFile";
  $Timer = time();
  
  if (system($command) != 0)
  {
    PrintInfo("Error: Failed to launch diff script\n");
  }
  $Timer = time() - $Timer;
  print INFO "Time to compare files: " . StoHMS($Timer) . " [$Timer second(s)]\n";
}
else
{
  PrintInfo("\nSkipping comparison.\n");
}

ExitScript();


###############
# Subroutines #
###############

# Collect and init platform specific data
sub GetPlatformData
{
  my $S_Machine;
  unless ($LocalHost = `uname -n`) {
	$LocalHost = `hostname`;  ## For Windows
  }
  chop($LocalHost);
  # Remove possibly present trailing ".Silvaco.COM"
  $LocalHost =~ s/\..*$//o;

  chop($UserName = `whoami`);
  $UserName =~ /\\(\S+)/;
  $UserName = $1;
  my ($vendor, $version, $hardware) = ("", "" ,"");
  if (my $uname = `uname -s -r -m`) {
	($vendor, $version, $hardware) = split ' ', $uname, 3;
  } else {
	$vendor = "MS";
	my $version_str = `ver`;
	$version_str =~ /Version (\d+\.\d+)/;
	$version = $1;
	my $hardware_str = `echo %PROCESSOR_IDENTIFIER%`;
	$hardware_str =~ /^(\S+) /;
	$hardware = $1;
  }
 #print "$vendor, $version, [$hardware]\n";
  # Now do a little platform-specific fix-up.
  if ($OSNAME eq "solaris")
  {
    if ($hardware =~ /^sun\d\w+$/) { chop $hardware; }
  }
  $SystemName = "$hardware-$vendor-$version";
  print "$SystemName\n";

  $Home = "/home/$UserName";
  $Build = "/build/$UserName";

  if ($SystemName =~ /^sun4-SunOS-5/)
  {
    $SmartSpice = "$Build/cvs/build/objects/$SystemName/optimize_SC5-64/apps/smartspice/spice.exe";
    $InstalledSmartSpice = "sparc-solaris2/PSmartSpice.64";
    $S_Machine = "sparc-solaris2";
  }
  elsif ($SystemName =~ /^i86pc-SunOS-5/)
  {
    $SmartSpice = "$Build/cvs/build/objects/$SystemName/optimize_SC5-64/apps/smartspice/spice.exe";
    $InstalledSmartSpice = "x86_64-solaris/PSmartSpice.64";
    $S_Machine = "x86_64-solaris";
  }
  elsif ($SystemName =~ /^i686-Linux-2.4/)
  {
    $SystemName = "i686-RHEL-3";
    $SmartSpice = "$Build/cvs/build/objects/$SystemName/optimize_gcc-3.2.3/apps/smartspice/spice.exe";
    $InstalledSmartSpice = "i386-linux/PSmartSpice";
    $S_Machine = "i386-linux";
  }
  elsif ($SystemName =~ /^i686-Linux-2.6/)
  {
    $SystemName = "i686-RHEL-5";
    $SmartSpice = "$Build/cvs/build/objects/$SystemName/optimize/apps/smartspice/spice.exe";
    $InstalledSmartSpice = "i386-linux/PSmartSpice";
    $S_Machine = "i386-linux";
  }
  elsif ($SystemName =~ /^x86_64-Linux-2.4/)
  {
    $SystemName = "x86_64-RHEL-3";
    $SmartSpice = "$Build/cvs/build/objects/$SystemName/optimize_gcc-3.2.3-64/apps/smartspice/spice.exe";
    $InstalledSmartSpice = "x86_64-linux/PSmartSpice";
    $S_Machine = "x86_64-linux";
  }
  elsif ($SystemName =~ /^x86_64-Linux-2.6/)
  {
    $SystemName = "x86_64-RHEL-4";
    $SmartSpice = "$Build/cvs/build/objects/$SystemName/optimize_gcc-4.5.0-64/apps/smartspice/spice.exe";
    $InstalledSmartSpice = "x86_64-linux/PSmartSpice";
    $S_Machine = "x86_64-linux";
    $ENV{"LD_LIBRARY_PATH"} = "/site/alpha/lib/support/x86_64-linux:$ENV{'LD_LIBRARY_PATH'}";
  }
  elsif ($SystemName =~ /^i686-CYGWIN_NT-6.1-WOW64/) # Must be above "elsif ($SystemName =~ /^i686-CYGWIN_NT/)"
  {
    $SystemName = "x86_64-windows";
    $Home = "/cygdrive/d/$UserName";
    $Build = "/cygdrive/d/$UserName";
    $SmartSpice = "$Build/cvs/build/objects/x64/Release/apps/smartspice7/smartspice.exe";
	           #d:\pavelh\cvs\build\objects\x64\Release\apps\smartspice7\smartspice.exe
    $InstalledSmartSpice = "x86_64-windows/smartspice.exe";
    $S_Machine = "x86_64-windows";
  }
  elsif ($SystemName =~ /^x86_64-CYGWIN_NT/)
  {
    $SystemName = "x86-NT";
    $Home = "/$UserName";
    $Build = "/$UserName";
    $SmartSpice = "$Build/cvs/build/objects/$SystemName/optimize/apps/smartspice/smartspice.exe";
    $InstalledSmartSpice = "x86-nt/smartspice.exe";
    $S_Machine = "x86-nt";
  }
  elsif ($SystemName =~ /^Intel64-MS-/)
  {
    $SystemName = "x86_64-windows";
    $Home = "$UserName";
    $Build = "$UserName";
    $SmartSpice = "smartspice";
	           #d:\pavelh\cvs\build\objects\x64\Release\apps\smartspice7\smartspice.exe
    $InstalledSmartSpice = "x86_64-windows/smartspice.exe";
    $S_Machine = "x86_64-windows";
  }
  else
  {
    die "\nError: Unknown platform: $SystemName\n\n";
  }

  # Setup path defaults
  $TestsPath = "$Home/cvs/qa/smartspice/tests";
  $RefTestsRepository = "/main/spice/lib/tests";
  $OutPath = "$Build/tests";
  $SFLMoverridesPath = "/main/alpha/etc/.overrides-V6";
  $ENV{"S_MACHINE"} = $S_Machine;
}

# Find latest available reference test results version
sub FindRefTestsVersion
{
  if (opendir(RTRDIR, $RefTestsRepository))
  {
    my @RefTestsVersions = grep /^\d+\.\d+\.\d+\.[ABCER]$/, readdir RTRDIR;
    @RefTestsVersions = sort
    {
      (split /\./, $a)[0] <=> (split /\./, $b)[0] or
        (split /\./, $a)[1] <=> (split /\./, $b)[1] or
          (split /\./, $a)[2] <=> (split /\./, $b)[2] or
            (split /\./, $a)[3] cmp (split /\./, $b)[3] 
    } @RefTestsVersions;
    closedir RTRDIR;
    $RefTestsVersion = $RefTestsVersions[$#RefTestsVersions];
  }
  if (!$RefTestsVersion)
  {
    $RefTestsVersion = "?";
  }
  $RefTestsPath = "$RefTestsRepository/$RefTestsVersion/$SystemName";
}

# Print info
sub PrintInfo
{
  my $Info = shift;
  print INFO $Info;
  print $Info;
}

# Convert seconds to HH:MM:SS format
sub StoHMS
{
  my $Time = shift;
  my ($hours, $minutes, $seconds);
  my $string;

  $hours = int($Time / 3600);
  $Time -= $hours * 3600;
  $minutes = int($Time / 60);
  $Time -= $minutes * 60;
  $seconds = int($Time);
  $string = sprintf("%02s:%02s:%02s", $hours, $minutes, $seconds);
  return($string);
}

# Read InOrder ini file - it contains list of deck dirs, decks from which
# should be run in order. File is searched in home dir first and then in script dir.
sub ReadInOrderINIFile
{
  if (-e "$Home/SSTestInOrder.ini") { $InOrderINI = "$Home/SSTestInOrder.ini"; }
  elsif (-e "$ScriptPath/SSTestInOrder.ini") { $InOrderINI = "$ScriptPath/SSTestInOrder.ini"; }
  else
  {
    PrintInfo("Warning: Could not find SSTestInOrder.ini file.\n");
    return;
  }
  open(INI, $InOrderINI);
  my $line;
  while ($line = <INI>)
  {
    # remove comments and blanks
    $line =~ s/[#\*].*$//o;
    $line =~ s/\s+//g;
    if ($line) { push @InOrderDirs, $line; }
  }
  close(INI);
  PrintInfo("$InOrderINI\n");
}

# Read IgnoreDirs ini file - it contains list of deck dirs, which should
# be ignored. File is searched in home dir.
sub ReadIgnoreDirsINIFile
{
  if (-e $IgnoreDirsINI) { open(INI, $IgnoreDirsINI); }
  else { return; }
  my $line;
  while ($line = <INI>) {
    # remove comments and blanks
    $line =~ s/[#\*].*$//o;
    $line =~ s/\s+//g;
    if ($line) { push @IgnoreDirs, $line; }
  }
  close(INI);
  PrintInfo("$IgnoreDirsINI\n");
}

# Exit script
sub ExitScript
{
  my ($sec, $min, $hour, $mday, $mon, $year, $wday, $yday, $isdst) = localtime(time);
  $EndTime = sprintf("%s %s %02d %02d:%02d:%02d %4d", 
                    ("Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat")[$wday],
                    ("Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec")[$mon],
                     $mday, $hour, $min, $sec, 1900+$year);
  $TotalTimer = time() - $TotalTimer;
  PrintInfo("\nStarted    : $StartTime\n");
  PrintInfo("Finished   : $EndTime\n");
  PrintInfo("Total time : " . StoHMS($TotalTimer) . " [$TotalTimer second(s)]\n\n");
  close(INFO);
  exit;
}

# Exit script if fatal flag was set (Parallel mode)
sub ExitOnFatal
{
  close(DECKLIST);
  close(PROGRESS);
  # Wait for all processes to die.
  my $pid;
  do
  { 
   $pid = waitpid(-1,&WNOHANG);
  }
  until $pid == -1;
  unlink("$Temp/.decklist");
  unlink("$Temp/.progress");
  unlink("$Temp/.fatal");
  rmdir("$Temp");
  open(INFO, ">>$InfoFile");
  ExitScript();
}

# Lock file
sub Lock
{
  my $file = shift;
  my $struct = pack("ssx32", F_WRLCK, 0);
  if (fcntl($file, F_SETLKW, $struct) == -1)
  {
    print "Error [$ProcessId]: fcntl failed. Terminating.\n";
    ExitOnFatal();
  }
}

# Unlock file
sub Unlock
{
  my $file = shift;
  my $struct = pack("ssx32", F_UNLCK, 0);
  if (fcntl($file, F_SETLKW, $struct) == -1)
  {
    print "Error [$ProcessId]: fcntl failed. Terminating.\n";
    ExitOnFatal();
  }
}

# Prepare tests.
# 1. Copy input deck tree.
# 2. Build list of input decks.
# In case of solver test mode, build 3 instances of the tests tree
sub PrepareTests
{
  my $Src  = $TestsPath;
  my $Dest = $OutPath;
  my $SolverDestDefault = "$Dest/default";
  my $SolverDestXMS = "$Dest/xms";
  my $SolverDestSpeeds = "$Dest/speeds";
  my @AllFiles = ();

  print "Building file list...";
  sub BuildListOfFiles
  {
    my $name = $File::Find::name;
	#print "BUILD $name\n";
    if (!($name =~ /CVS/ ))
    {
      my $IgnoreDir;
      my $skip = 0;
      # Check IgnoreDirs wether this deck should be skipped.
      foreach $IgnoreDir (@IgnoreDirs)
      {
        if ($name =~ /$IgnoreDir/i)
        {
          $skip = 1;
          last;
        }
      }
      if (!$skip)
      {
        if (-f $name) { push @AllFiles, $name; }
        if (-d $name)
        {
          if ($SolversTestMode)
          {
            my $name_default = $name;
            my $name_xms = $name;
            my $name_speeds = $name;
            $name_default =~ s/$Src/$SolverDestDefault/o;
            $name_xms =~ s/$Src/$SolverDestXMS/o;
            $name_speeds =~ s/$Src/$SolverDestSpeeds/o;
            if (!(-e $name_default)) { File::Path::mkpath($name_default, 0, 0755) or die "\nError: Failed to make directory\n[$name_default]\n"; }
            if (!(-e $name_xms)) { File::Path::mkpath($name_xms, 0, 0755) or die "\nError: Failed to make directory\n[$name_xms]\n"; }
            if (!(-e $name_speeds)) { File::Path::mkpath($name_speeds, 0, 0755) or die "\nError: Failed to make directory\n[$name_speeds]\n"; }
          }
          else
          {
            $name =~ s/$Src/$Dest/o;
            if (!(-e $name))
            {
              File::Path::mkpath($name, 0, 0755) or die "\nError: Failed to make directory\n[$name]\n";
            }
          }
        }
      }
    }
  }
  find (\&BuildListOfFiles, $Src);
  my $TotalFiles = $#AllFiles + 1;
  print " Done. [$TotalFiles file(s)]\n";
  if ($#AllFiles == -1) { die "\nError: Failed to copy input decks or no decks were found.\n"; }
  print "                   [0%                     50%                    100%]\n";
  print "Copying test files [";

  my $DotsPerFile = 50 / $TotalFiles;
  my $DotsAccumulated = 0;
  my $DotsPlaced = 0;
  my $file;
  foreach $file (@AllFiles)
  {
	##print "==== $file\n";
    my $dest = $file;
    if ($SolversTestMode)
    {
      my $dest_default = $dest;
      my $dest_xms = $dest;
      my $dest_speeds = $dest;
      $dest_default =~ s/$Src/$SolverDestDefault/o;
      $dest_xms =~ s/$Src/$SolverDestXMS/o;
      $dest_speeds =~ s/$Src/$SolverDestSpeeds/o;
      copy($file, $dest_default) or die "\nError: Failed to copy $file to $dest_default\n";
      chmod 0755, $dest_default;
      copy($file, $dest_xms) or die "\nError: Failed to copy $file to $dest_xms\n";
      chmod 0755, $dest_xms;
      copy($file, $dest_speeds) or die "\nError: Failed to copy $file to $dest_speeds\n";
      chmod 0755, $dest_speeds;
    }
    else
    {
      $dest =~ s/$Src/$Dest/o;
      copy($file, $dest) or die "\nError: Failed to copy $file to $dest\n";
      chmod 0755, $dest;
    }
    $DotsAccumulated += $DotsPerFile;
    my $dots = int $DotsAccumulated;
    if ($dots > 0)
    {
      print "." x $dots;
      $DotsAccumulated -= $dots;
      $DotsPlaced += $dots;
    }
  }
  if ($DotsPlaced < 50) { print "." x (50 - $DotsPlaced); }
  print "] Done.\n";

  # Build list of input decks
  print "Building deck list [";

  $DotsAccumulated = 0;
  $DotsPlaced = 0;
  foreach $file (@AllFiles)
  {
    if ($file =~ /.*\.(in|sp|hsim)$/)
    {
      if ($Parallel)
      {
        my $iodir;
        my $iodeck = 0;
        # Check InOrderDirs wether this deck should be run
        # in in-order mode. Push it in @InOrderDecks array then.
        # If not, push it into @ParallelDecks array.
        foreach $iodir (@InOrderDirs)
        {
          if ($file =~ /$iodir/i)
          {
            if ($SolversTestMode)
            {
              my $file_default = $file;
              my $file_xms = $file;
              my $file_speeds = $file;
              $file_default =~ s/$Src/$SolverDestDefault/o;
              $file_xms =~ s/$Src/$SolverDestXMS/o;
              $file_speeds =~ s/$Src/$SolverDestSpeeds/o;
              push @InOrderDecks, $file_default;
              push @InOrderDecks, $file_xms;
              push @InOrderDecks, $file_speeds;
            }
            else
            {
              $file =~ s/$Src/$Dest/o;
              push @InOrderDecks, $file;
            }
            $iodeck = 1;
            last;
          }
        }
        if (!$iodeck)
        {
          if ($SolversTestMode)
          {
            my $file_default = $file;
            my $file_xms = $file;
            my $file_speeds = $file;
            $file_default =~ s/$Src/$SolverDestDefault/o;
            $file_xms =~ s/$Src/$SolverDestXMS/o;
            $file_speeds =~ s/$Src/$SolverDestSpeeds/o;
            push @ParallelDecks, $file_default;
            push @ParallelDecks, $file_xms;
            push @ParallelDecks, $file_speeds;
          }
          else
          {
            $file =~ s/$Src/$Dest/o;
            push @ParallelDecks, $file;
          }
        }
      }
      else
      {
        if ($SolversTestMode)
        {
          my $file_default = $file;
          my $file_xms = $file;
          my $file_speeds = $file;
          $file_default =~ s/$Src/$SolverDestDefault/o;
          $file_xms =~ s/$Src/$SolverDestXMS/o;
          $file_speeds =~ s/$Src/$SolverDestSpeeds/o;
          push @InOrderDecks, $file_default;
          push @InOrderDecks, $file_xms;
          push @InOrderDecks, $file_speeds;
        }
        else
        {
          $file =~ s/$Src/$Dest/o;
          push @InOrderDecks, $file;
        }
      }
    }
    $DotsAccumulated += $DotsPerFile;
    my $dots = int $DotsAccumulated;
    if ($dots > 0)
    {
      print "." x $dots;
      $DotsAccumulated -= $dots;
      $DotsPlaced += $dots;
    }
  }
  if ($DotsPlaced < 50) { print "." x (50 - $DotsPlaced); }
  
  if ($RunTMIscript)
  {
    print "] Ignore TMI decks...";
	my $tmiDecks = 0;
	my %tmiPackagePath = ();

	sub RemoveTMIDecksFromList
	{
		my @decks = @{(shift)};
		my $tmiPackage = shift;
		my $i;
		
		for ($i = 0; $i < $#decks + 1; $i++) 
		{
			my $file = @decks[$i];
			
			if ($file =~ /\/$tmiPackage/i)
			{
			  my $ind = index($file, $tmiPackage);
			  my $file_dir = substr($file, 0, $ind)."$tmiPackage";

			  if (exists  $tmiPackagePath{ $file_dir })
			  {
  			    $tmiPackagePath{$file_dir} = $tmiPackagePath{$file_dir} + 1;
			  }
		      else
			  {
			    $tmiPackagePath{$file_dir} = 1;
			  }
			  
			  #push @tmiPackagePath, $file_dir;
			  
			  splice (@decks, $i, 1);
			  $i--;
			  $tmiDecks++;
			}
		}
		
		return @decks;
	}
	
	@ParallelDecks = RemoveTMIDecksFromList(\@ParallelDecks, "TMI2d0d1_2012_0626");
	@InOrderDecks = RemoveTMIDecksFromList(\@InOrderDecks, "TMI2d0d1_2012_0626");
	
	@ParallelDecks = RemoveTMIDecksFromList(\@ParallelDecks, "TMI2d0d1_2013_0830");
	@InOrderDecks = RemoveTMIDecksFromList(\@InOrderDecks, "TMI2d0d1_2013_0830");

	@ParallelDecks = RemoveTMIDecksFromList(\@ParallelDecks, "TMI2d0d1_2014_0501a");
	@InOrderDecks = RemoveTMIDecksFromList(\@InOrderDecks, "TMI2d0d1_2014_0501a");
	
	#TODO: add more TMI packages

	while (my ($key, $value) = each(%tmiPackagePath) )
	{
        push @TMIdirs, $key;
    }	
	
    print " Done. [$tmiDecks TMI deck(s)] Sorting...";
  }
  else
  {
  print "] Sorting...";
  }
  
  @ParallelDecks = sort @ParallelDecks;
  @InOrderDecks = sort @InOrderDecks;

  $TotalDecks = ($#ParallelDecks + 1) + ($#InOrderDecks + 1);
  print " Done. ($TotalDecks decks)\n";
}

# Run tests
sub RunDecks
{
  my $deck;

  # Create temporary files with data for parallel processes.
  if ($Parallel)
  {
    # List of decks to run.
    open(DECKLIST, ">$Temp/.decklist");
    foreach $deck (@ParallelDecks) { print DECKLIST "$deck\n"; }
    close(DECKLIST);

    # Progress indicator.
    open(PROGRESS, ">$Temp/.progress");
    print PROGRESS "0\n";
    close(PROGRESS);

    print "Starting simulations...\n";
    print "Parallel mode engaged using $Parallel CPUs\n";
    print "[Current/ ][Real][    CPU][   Total]\n";
    print "[   /Total][time][   time][    time][P] [Test file]\n";

    my $ChildrenToFork = $Parallel - 1;
    my $pid = -1;
    my $ChildId = 0;

    # Fork children.
    while ($ChildrenToFork)
    {
      $ChildId++;
      $pid = fork;
      if ($pid)
      {
        $ChildrenToFork--;
      }
      else
      {
        $ChildrenToFork = 0;
      }
    }
    my $OldFH;
    # Turn on autoflush for decklist and progress files' filehandles
    # so each write will be reflected immediately.
    open(DECKLIST, "+<$Temp/.decklist");
    $OldFH = select(DECKLIST); $| = 1; select($OldFH);
    open(PROGRESS, "+<$Temp/.progress");
    $OldFH = select(PROGRESS); $| = 1; select($OldFH);
print "++++++++++++++++++++++++++++++++++++++\n";
    if ($pid)
    {
      # PARENT
      $ProcessId = 0;

      # In-order decks
      if ($#InOrderDecks != -1)
      { 
        my $DeckFile;
        foreach $DeckFile (@InOrderDecks)
        {
          # Check if fatal error has occured
          if (-e ("$Temp/.fatal"))
          {
            print "Error [$ProcessId]: Fatal error detected. Terminating.\n";
            ExitOnFatal();
          }
          RunSmartSpice($DeckFile);
        }
      }

      # Parallel Decks
      RunParallelDecks();

      close(DECKLIST);
      close(PROGRESS);
    }
    else
    {
      # CHILDREN
      $ProcessId = $ChildId;

      # Parallel Decks
      RunParallelDecks();

      close(DECKLIST);
      close(PROGRESS);
      exit;
    }

    # Wait for all processes to die.
    do
    {
      $pid = waitpid(-1,&WNOHANG);
      sleep(1); # prevent use 100% of cpu when the last decks is simulating
    }
    until $pid == -1;  

    unlink("$Temp/.decklist");
    unlink("$Temp/.progress");
  }
  else
  {
    # Single CPU run
    print "Starting simulations...\n";
    print "[Current/ ][Real][    CPU][   Total]\n";
    print "[   /Total][time][   time][    time] [Test file]\n";

    if ($#InOrderDecks != -1)
    { 
      my $DeckFile;
      my $count = 1;
      foreach $DeckFile (@InOrderDecks)
      {
        RunSmartSpice($DeckFile, $count);
        $count++;
      }
    }
  }

  rmdir("$Temp");
}

# Run parallel decks
# Parent & children processes use this sub when run in parallel mode
sub RunParallelDecks
{
  for (;;)
  {
    # Check if fatal error has occured
    if (-e ("$Temp/.fatal"))
    {
      print "Error [$ProcessId]: Fatal error detected. Terminating.\n";
      ExitOnFatal();
    }

    my $DeckFile;
    my $FPos;
    # Lock decklist. Parse it until line starts with /, not with +
    # + means that deck has already been run.
    # Get line and put + at it's start.
    # Unlock decklist, so other processes could grab their entries.
    Lock(\*DECKLIST);
    seek(DECKLIST, 0, 0);
    for (;;)
    {
      $FPos = tell(DECKLIST);
      $DeckFile = <DECKLIST>;
      if (!$DeckFile) { last; }
      if (!($DeckFile =~ /^\+/))
      {
        seek(DECKLIST, $FPos, 0);
        print DECKLIST "+";
        last;
      }
    }
    Unlock(\*DECKLIST);
    if ($DeckFile)
    {
      $DeckFile =~ s/\s+$//g;
      RunSmartSpice($DeckFile, $ProcessId);
    }
    else { last; }
  }
}

# Run SmartSpice
my @all_children = ();
my $wer_pid;
my $rundll_pid;
sub RunSmartSpice
{
  my $DeckFile = shift;
  my $count = shift;
  #print "DeckFile [$DeckFile]\n";
  my ($Deck, $DeckPath, $suffix) = fileparse($DeckFile);
  #print "$Deck, $DeckPath, $suffix ST $StatusFile\n";
  my $DeckName = $Deck;
  $DeckName =~ s/\.(in|sp|hsim)$//;
  chdir($DeckPath);
system("echo $DeckFile >> $StatusFile");
  # Read command line options for this deck if .$Deck.clopts
  # or .LastDirName.clopts are present
  my $CLOpts = $BatchMode;
  my $CLOptsFile = ".$Deck.clopts";
  my $CLOptsDirFile = ".directory.clopts";
  my $CLOptsDirTreeFile = ".dirtree.clopts";
  if (-e $CLOptsFile)
  {
    open(CLO, $CLOptsFile);
    $CLOpts = <CLO>;
    $CLOpts =~ s/\s+$//g;
    close(CLO);
  }
  elsif (-e $CLOptsDirFile)
  {
    open(CLO, $CLOptsDirFile);
    $CLOpts = <CLO>;
    $CLOpts =~ s/\s+$//g;
    close(CLO);
  }
  else
  {
    my $ParentDirPath = $DeckPath;
	my $do_while = 1;

	while ($do_while)
	{
		#print "looking for $CLOptsDirTreeFile at $ParentDirPath\n";
	    chdir($ParentDirPath);
		
		if (-e $CLOptsDirTreeFile)
		{
			open(CLO, $CLOptsDirTreeFile);
			$CLOpts = <CLO>;
			$CLOpts =~ s/\s+$//g;
			close(CLO);
			
			$do_while = 0;
		}
		elsif ($ParentDirPath eq $OutPath)
		{
			$do_while = 0;
		}
		else
		{
			my @dirs = split('/', $ParentDirPath);
			pop(@dirs);
			$ParentDirPath = join('/', @dirs);
		}
    if( $ParentDirPath eq '') {$do_while = 0;}
	}
	
    chdir($DeckPath);	
  }

  my $Time = time();
  my $CPUTime = (times)[0] + (times)[2];
  my $Repeat = 1;
  my $RepCLOptsFile;

  # In solvers test mode supply solver forcing flags to SmartSpice
  my $SolverFlag = "";
  if ($SolversTestMode)
  {
    if ($DeckFile =~ /^$OutPath\/default/) { $SolverFlag = "-forcesolver default"; }
    elsif ($DeckFile =~ /^$OutPath\/xms/) { $SolverFlag = "-forcesolver xms"; }
    else { $SolverFlag = "-forcesolver speeds"; }
  }
  my $run_status = "";
  do
  {
    if ($SystemName =~ /^x86-NT/ || $SystemName =~ /^x86_64-windows/)
    {
		my $cmd_str = "$SmartSpice $CLOpts $SmartSpiceFlags $SolverFlag $Deck -o $DeckName.out -e $DeckName.err";
		
		print $cmd_str , "\n";
		my $ProcessObj;
		Win32::Process::Create($ProcessObj,"C:\\ams2015\\exe\\smartspice.exe",$cmd_str,0,NORMAL_PRIORITY_CLASS , ".") or
			die ErrorReport();
		my $pid = $ProcessObj->GetProcessID();
		my $wait_t0 = time();
		
		my $exitcode;
		my $loop_check = ""; # OK or WER
		my $rundll_str="";
		my $rundll_cnt=0;
		$run_status = "D"; ##Done
		while (1) {
			print ("parent waiting $pid\n");
			$ProcessObj->Wait(3000);
			$ProcessObj->GetExitCode($exitcode);
			if ($exitcode == STILL_ACTIVE) {
				print "STILL_ACTIVE $exitcode" . " and " . STILL_ACTIVE . "\n";
				$loop_check = wait_n_check($pid , $DeckName);
				if ($loop_check eq "WER") {
					close_wer($wer_pid , $pid);
					$run_status = "C"; ## crash
					last;
				} elsif ($loop_check eq "RUNDLL") {
					$rundll_cnt = close_rundll($rundll_pid , $rundll_cnt);
					if ($rundll_cnt > 99) {
						$run_status = "H"; ## Hang in dialog
						last;
					}
				} elsif ($loop_check eq "ERRLOOP") {
					$ProcessObj->Kill(99);
					$run_status = "L"; ##Error Loop
				}
			} else {
				print "NOT Active $exitcode\n";
				last;
			}
		}
		my $wait_t1 = time();
		print "exit code $exitcode wait=[" , $wait_t1 - $wait_t0 , "]\n";


    }
	system("echo %DATE% %TIME% $run_status >> $StatusFile");

    
    $RepCLOptsFile = "${CLOptsFile}$Repeat";
    if (-e $RepCLOptsFile)
    {
      open(CLO, $RepCLOptsFile);
      $CLOpts = <CLO>;
      $CLOpts =~ s/\s+$//g;
      close(CLO);
      $Repeat++;
    }
    else { $Repeat = 0; }
  }
  while ($Repeat != 0);

  $CPUTime = (times)[0] + (times)[2] - $CPUTime;

  # Check errors
  my $case_f = $DeckPath;
  $case_f =~ s/$OutPath//;
  ##$run_status = 'D'; #Done
  my $err;
  open(ERRFILE, "$DeckName.err");
  $err = <ERRFILE>;
  if ($err)
  {
    if ($err =~ /$SmartSpice/i)
    {
      $err =~ s/\s+$//g;
	  $run_status = 'E' unless ($run_status eq "C");  #Error
      if ($Parallel)
      {
        print "Error [$ProcessId]: Failed to execute SmartSpice.\n[$err]\n";
        close(ERRFILE);
        open(TMP, ">$Temp/.fatal");
        close(TMP);
#        ExitOnFatal();
      }
      else
      {
        print "Error: Failed to execute SmartSpice.\n[$err]\n";
        rmdir("$Temp");
#        ExitScript();
      }
    }
    do
    {
      if ($err =~ /segmentation violation/i)
      {
		$run_status = 'S' unless ($run_status eq "C"); #segmentation fault
        $DeckPath =~ s/$OutPath//;
        if ($Parallel)
        {
          open(INFO, ">>$InfoFile");
          Lock(\*INFO);
          PrintInfo("Error [$ProcessId]: \"Segmentation Violation\" detected in $DeckPath$DeckName.err\n");
          Unlock(\*INFO);
          close(INFO);
        }
        else { PrintInfo("Error: \"Segmentation Violation\" detected in $DeckPath$DeckName.err\n"); }
      } 
      if ($err =~ /bus error/i)
      {
		$run_status = 'B' unless ($run_status eq "C"); #bus error
        $DeckPath =~ s/$OutPath//;
        if ($Parallel)
        {
          open(INFO, ">>$InfoFile");
          Lock(\*INFO);
          PrintInfo("Error [$ProcessId]: \"Bus Error\" detected in $DeckPath$DeckName.err\n");
          Unlock(\*INFO);
          close(INFO);
        }
        else { PrintInfo("Error: \"Bus Error\" detected in $DeckPath$DeckName.err\n"); }
      }
    } while ($err = <ERRFILE>); 
  } 
  close(ERRFILE);

  # Statistics
  $Time = time() - $Time;
  $DeckFile =~ s/$OutPath\///;
  if ($Parallel)
  {
    # Lock progress indicator file and update counter in it.
    Lock(\*PROGRESS);
    seek(PROGRESS, 0, 0);
    $count = <PROGRESS>;
    $count =~ s/\s+$//g;
    $count++;
    seek(PROGRESS, 0, 0);
    print PROGRESS "$count\n";
    Unlock(\*PROGRESS);
    printf("[%04d/%04d][%4d][%7.2f][%s][%d] %s\n", $count, $TotalDecks, $Time, $CPUTime, StoHMS(time() - $Timer), $ProcessId, $DeckFile);
  }
  else
  {
    printf("[%04d/%04d][%4d][%7.2f][%s] %s\n", $count, $TotalDecks, $Time, $CPUTime, StoHMS(time() - $Timer), $DeckFile);
  }
  	add_test($sql_idx , $Deck , $case_f , $run_status ,
	 0 , 0 , 0 , $CPUTime, 0,
	 0, 0);
}

# Print help screen
sub PrintHelp {
  print <<EOF;

Usage: 

SSTest [-c path] [-w version] [-e executable] [-f "flags"] [-h] [-i version] [-m]
       [-o path] [-p num] [-r path] [-s] [-t] [-x invocation] [-z] [-g ignore_dirs_filepath] [-T]
       [testsdir]

 -c  Custom tests path
     (default: $TestsPath)
 -w  Test SIPC used installed version TestSpice (Last: 1.2.5.A)
 -e  Test custom executable
     (default: $SmartSpice)
 -f  Flags for executable (default: $SmartSpiceFlags)
 -h  Print this help message
 -i  Test installed executable
 -m  Do not override SFLM
 -n  Test override SFLM for next month
 -o  Output directory (default: $OutPath)
 -p  Number of processes to run in parallel
 -r  Custom reference directory to compare results with
     (default: $RefTestsPath)
 -s  Use "-sb" (silent batchmode) instead of "-b" flag for batchmode
 -t  Terminate after running decks, do not run diffs
 -x  Use script launcher invocation for executable
 -z  Solvers test mode (implies -t)
 -g  Set a custom ignore dirs file
 -d  Daily testing mode - does not create platfrom specific folders such as 'x86_64-RHEL-4-1023-0'
 -T  Test TMI packages with TSMC evaluation script

 testsdir: run tests from this directory and subdirectories 
           (default: run all tests)
EOF
  die "\n";
}
sub wmic_extract
{
	my $pid = shift;
	my $filename0 = "wmic_" . $pid ."0.txt";
	my $filename = "wmic_" . $pid .".txt";
	system("wmic process get Caption,ParentProcessId,ProcessId,ExecutablePath,CommandLine > $filename0");
	system("type $filename0 > $filename");
	my %wmic_pos = ();
	my %pos_wmic = ();
	my @pos_arr = ();
	my @wmic_all = ();
	my $line_cnt = 0;
	open(WMIC , "<" , "$filename");
	while (<WMIC>) {
		my $head = $_;
		if ($line_cnt == 0) {
			while ($head =~ /(\S+)/gi) {
				my $key1 = $1;
				my $pos1 = pos($head) - length $key1;
				push(@pos_arr , $pos1);
				$wmic_pos{$key1} = $pos1;
				$pos_wmic{$pos1} = $key1;
			}
			push (@pos_arr , length($head) - 1);
		} else {
			my %one_rec = ();
			for (my $i = 0 ; $i < $#pos_arr ; $i++) {
				my $pos = $pos_arr[$i];
				my $len = $pos_arr[$i+1] - $pos - 1;
				my $item = $pos_wmic{$pos};
				my $val = substr($head , $pos , $len);
				$val =~ s/\s$//;
				$one_rec{$item} = $val;
			}
			push(@wmic_all , \%one_rec);
		}
		$line_cnt++;
	}
	close(WMIC);
	return \@wmic_all;
}
sub wait_n_check{
	my $pid = shift;
	my $deckN = shift;
	my $wmic_ref = wmic_extract($pid);
	my @wmic = @$wmic_ref;
	
	## @all_children will store all child pid of $pid by the wmic result
	our @all_children = ();
	my %already_added = ();
	my $added = 1;
	while ($added) {
		$added = 0;
		for my $wmic (@$wmic_ref) {
			next if (defined($already_added{$wmic->{ProcessId}}));
			if ($wmic->{ParentProcessId} == $pid) {
				push(@all_children , $wmic->{ProcessId});
				$already_added{$wmic->{ProcessId}} = 1 ;
				$added++;
			} else {
				for my $already_child (@all_children) {
					if ($wmic->{ParentProcessId} == $already_child) {
						push(@all_children , $wmic->{ProcessId});
						$already_added{$wmic->{ProcessId}} = 1 ;
						$added++
					}
				}
			}
		}
	}

	## Check WerFault.exe
	for my $wmic (@$wmic_ref) {
		if ($wmic->{Caption} =~ /^WerFault.exe/) {
			if ($wmic->{CommandLine} =~ /-p (\d+) /) {
				my $err_pid = $1;
				for (@all_children) {
					$wer_pid = $wmic->{ProcessId};
					return "WER" if ($err_pid == $_);
				}
			}
		}
	}
	## Check unexpected command
	for my $wmic (@$wmic_ref) {
		if ($wmic->{Caption} =~ /^rundll32.exe/) {
			$rundll_pid = $wmic->{ProcessId};
			return "RUNDLL";
		}
	}
	
	## check deck err file
	my $err_file = $deckN . ".err";
	my @err_arr = ();
	my $max_err_arr = 1000;
	my $line_cnt = 0;
	open (ERRF , "<" , $err_file);
	while (<ERRF>) {
		chomp;
		if (/^Error/) {
			push(@err_arr , $_);
			$line_cnt++;
			if ($line_cnt > $max_err_arr) {
				shift(@err_arr);
			}
		}
	}
	close(ERRF);
	if ($line_cnt > $max_err_arr) {
		my %err_dup = ();
		for my $err (@err_arr) {
			if ( exists ($err_dup{$err})) {
				$err_dup{$err}++;
			} else {
				$err_dup{$err} = 1;
			}
		}
		my $max_dup = 0;
		my $num_dup_key = 0;
		while ( my ($key, $value) = each(%err_dup) ) {
			$num_dup_key++;
			if ($max_dup < $value) {
				$max_dup = $value;
			}
		}
		print "number of keys in $max_err_arr is $num_dup_key , the most happen is $max_dup times\n";
		if ($max_dup > $max_err_arr/20 or $num_dup_key < $max_err_arr/100) {
			for my $wmic (@$wmic_ref) {
				if ($wmic->{ParentProcessId} == $pid) {
					my $child_pid = $wmic->{ProcessId};
					Win32::Process::KillProcess($child_pid, 99);
				}
			}
			return "ERRLOOP"
		}
	}
	
	return "OK";
}

sub close_wer{
	my $wer_pid = shift;
	my $ss_pid = shift;
	system("echo wer_pid = [$wer_pid] ss_pid = [$ss_pid] >> $StatusFile");
	my @allWin = FindWindowLike(undef , "");

	for (@allWin) {
		my $pidLPDWORDStruct = pack("L" , 0);
		GetWindowThreadProcessId($_ , $pidLPDWORDStruct);
		my $pid = unpack("L" , $pidLPDWORDStruct);
		my $win_title = GetWindowText($_);
		if  ($pid == $wer_pid) {
			system("echo windows id [$_] pid [$pid] title [$win_title] >> $StatusFile");
		}
		
		#if ( ($pid == $wer_pid) and ($win_title eq "Close the program")) {
		if ( ($pid == $wer_pid) and (($win_title eq "Close the program") or ($win_title eq "&Close program"))) {
			SetFocus($_);
			SendKeys("~");
			my $focus = GetFocus($_);
			system("echo SendKeys post-check SetFocus $_ and GetFocus $focus >> $StatusFile");
			return;
		}
	}
}
sub close_rundll{
	my $rundll_pid = shift;
	my $cnt = shift;
	my @allWin = FindWindowLike(undef , "");

	my $cannot_open = 0;
	my $cancel = 0;
	my $cancel_wid;
	for (@allWin) {
		my $pidLPDWORDStruct = pack("L" , 0);
		GetWindowThreadProcessId($_ , $pidLPDWORDStruct);
		my $pid = unpack("L" , $pidLPDWORDStruct);
		my $win_title = GetWindowText($_);
		
		if ( ($pid == $rundll_pid) and ($win_title eq "Windows can't open this file:")) {
			$cannot_open = 1;
		}
		if ( ($pid == $rundll_pid) and ($win_title eq "Cancel")) {
			$cancel = 1;
			$cancel_wid = $_;
		}
	}
	if ($cannot_open and $cancel) {
		$cnt++;
		return $cnt if ($cnt <3);
		SetFocus($cancel_wid);
		SendKeys("~");
		return 100;;
	}
	return 0;
}

sub add_idx
{
	return unless ($QADB_UPLOAD);
        my ($prod, $ver ,$plat , $root_path) = @_;
# create the socket, connect to the port
        my $socket = open_client($port);
        until ($socket) {
                $port++;
                if ($port > $port_max) {
                        die "Use out of port from $port_init to $port_max\n";
                }
                $socket = open_client($port);
        }
##      print "open socket $socket\n";
        my $line;
        my $datetime = localtime;
        my $machine = `hostname`;
        chomp $machine;
        my $sql = "QADBINDX insert into test_idx ";
        $sql .= "(test_date, prod , ver , platform , root_path, machine) values ('";
        $sql .= $datetime . "','";
        $sql .= $prod   . "','";
        $sql .= $ver    . "','";
        $sql .= $plat   . "','";
        $sql .= $root_path   . "','";
        $sql .= $machine. "')\n";
        print $socket $sql;
        if (defined ($line = <$socket>)) {
                chomp $line;
                print "** From socket **";
                print "$line\n";
        }
        close($socket);
        return $line;
}
sub add_test
{
	return unless ($QADB_UPLOAD);
        my ($idx, $casename ,$casepath, $res, $wait_st, $mem, $usertime, $cputime, $elapsetime , $scripttime , $limittime) = @_;
# create the socket, connect to the port
        my $socket = open_client($port);
        until ($socket) {
                $port++;
                if ($port > $port_max) {
                        die "Use out of port from $port_init to $port_max\n";
                }
                $socket = open_client($port);
        }
##      print "open socket $socket\n";
        my $line;
        my $sql = "QADBTEST insert into test ";
        $sql .= "(test_id, casename, case_path, result, wait_status, ";
        $sql .= "memory , user_time , cpu_time , elapse_time, ";
	$sql .= "script_wait , limit_time) values (";
        $sql .= $idx            . ",'";
        $sql .= $casename       . "','";
        $sql .= $casepath       . "','";
        $sql .= $res            . "',";
        $sql .= $wait_st	. ",";
        $sql .= $mem            . ",";
        $sql .= $usertime       . ",";
        $sql .= $cputime        . ",";
        $sql .= $elapsetime     . ",";
        $sql .= $scripttime     . ",";
        $sql .= $limittime      . ")\n";
##      print $sql;
        print $socket $sql;
        if (defined ($line = <$socket>)) {
                chomp $line;
                print "** From socket **";
                print "$line\n";
        }
        close($socket);
        return $line;
}

sub open_client
{
my $port = shift;
my $server = "twlxws2810.silvaco.com";

my $socket = IO::Socket::INET->new(PeerAddr     => $server,
                                   PeerPort     => $port,
                                   Proto        => "tcp",
                                   Type         => SOCK_STREAM);
print "Can't create a socket [$server] [$port] $@\n" unless $socket;

return $socket;
}

#my ($cmd_name , $cmd_ver , $cmd_platform , $cmd_exe) = get_pv($s_pid);
sub get_pv{
system "start $SmartSpice";
sleep(1);
my @windows = FindWindowLike(undef, "smartspice");
system("wmic /output:ProcessList0.txt process");
system("type ProcessList0.txt > ProcessList.txt");
my (	$cmd_name , $cmd_ver , $cmd_platform , $cmd_exe) = ("","","","");
open(PS, "<" , "ProcessList.txt");
	while ( <PS>) {
		my $oneline = $_;
		chomp $oneline;
		#print "[$oneline]\n";
		if ( $oneline =~ /lib\\smartspice\\(\d+\.\d+\.\d+\.[ABCR])\\([^\\]+)\\(\S+)/ ) {
			print "matched [$1] [$2] [$3]\n";
			$cmd_name = 'smartspice';
			$cmd_ver  = $1;
			$cmd_platform = $2;
			$cmd_exe  = $3;
			last;
		}
	}
	close(PS);
	SendKeys("%f");
SendKeys("x");
sleep(1);

	return (	$cmd_name , $cmd_ver , $cmd_platform , $cmd_exe);
}

sub ErrorReport{
	print Win32::FormatMessage( Win32::GetLastError() );
}
