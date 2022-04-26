package Notify;
use strict;
use warnings;
use Data::Dumper;

my %notify_info = (
		token => "KJF2iAgrxJKTOVB5c6eh51NgAK92X8AGjundH98fINa" ,
		max_length => 900
	);

sub new
{
	my $class = shift;
	my $pid = shift;
	my $self = {
		hostname => `hostname -s`,
		main_pid => $pid,
		buffer => ""
	};
	bless $self , $class;
}
sub Notify
{
	my $msg = shift;
	my ($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time);
	
	my $message = "$hour:$min:$sec - $msg";
	my $cmd = "curl https:\/\/notify-api.line.me\/api\/notify -X POST ";
	$cmd .= "-H \"Authorization: Bearer " . $notify_info{token} . "\" ";
	$cmd .= "-F \"message=" . $message . "\"";
    my $out = `$cmd`;
}
sub notify
{
	my $self = shift;
	my $msg = shift;
	my $time_tag = shift || 1;
	my $flush = shift || 0;

	my ($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time);
	if ($time_tag) {
		$msg = "$hour:$min:$sec - $msg";
	}

	if (length($self->{buffer}) + length($msg) > $notify_info{max_length} ) {
		$self->notify_flush();
		$self->{buffer} = $msg;
	} else {
		if ($self->{buffer}) {
			$self->{buffer} .= "\n";
		}
		$self->{buffer} .= $msg;
	}

	if ($flush) {
		$self->notify_flush();
	}
}
sub notify_flush
{
	my $self = shift;
	line_flush("From " . $self->{hostname} . "\n" . $self->{buffer});
	$self->{buffer} = "";
}
sub line_flush
{
	my $message = shift;

	my $cmd = "curl https:\/\/notify-api.line.me\/api\/notify -X POST ";
	$cmd .= "-H \"Authorization: Bearer " . $notify_info{token} . "\" ";
	$cmd .= "-F \"message=" . $message . "\"";
    my $out = `$cmd`;
}

1;
