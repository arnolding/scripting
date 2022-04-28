#include <stdio.h>
#include <stdlib.h>
#include <X11/Xlib.h>

int main(int argc, char **argv)
{

// Refer http://stackoverflow.com/questions/2858263/how-do-i-bring-a-processes-window-to-the-foreground-on-x-windows-c
   Display *dsp = XOpenDisplay(NULL);
   long id = strtol(argv[1], NULL, 0);
   XSetWindowAttributes xswa;
   xswa.override_redirect=True;
   XChangeWindowAttributes (dsp,id,CWOverrideRedirect, &xswa);
   XRaiseWindow ( dsp, id );

	XEvent event = { 0 };
        event.xclient.type = ClientMessage;
        event.xclient.serial = 0;
        event.xclient.send_event = True;
        event.xclient.message_type = XInternAtom( dsp, "_NET_ACTIVE_WINDOW", False);
        event.xclient.window = id;
        event.xclient.format = 32;

        XSendEvent( dsp, DefaultRootWindow(dsp), False, SubstructureRedirectMask | SubstructureNotifyMask, &event );
        XMapRaised( dsp, id );
   XCloseDisplay ( dsp );


	
}
