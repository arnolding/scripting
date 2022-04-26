#include <stdio.h>
#include <stdlib.h>
#include <X11/Xlib.h>

int main(int argc, char **argv)
{

// Refer http://stackoverflow.com/questions/2858263/how-do-i-bring-a-processes-window-to-the-foreground-on-x-windows-c
   Window window;
   int revert;
   char *name;
   Display *dsp = XOpenDisplay(NULL);
   if (!dsp)
        fprintf(stderr, "Failed to open display!\n");
   XGetInputFocus(dsp, &window, &revert);
   XFetchName(dsp, window, &name);
   
   printf("window:%ld name:%s\n", window, name);




//   long id = strtol(argv[1], NULL, 10);
   XCloseDisplay ( dsp );
}
