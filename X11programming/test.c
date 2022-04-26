#include <stdio.h>
#include <X11/Xlib.h>
#include <X11/Xutil.h>
#include <X11/Xos.h>
main()
{
Display *dis;
int screen;
Window win;
GC gc;

unsigned long black, white;

dis = XOpenDisplay((char *)0);
screen=DefaultScreen(dis);
black = BlackPixel(dis,screen);
white = WhitePixel(dis,screen);

win=XCreateSimpleWindow(dis,DefaultRootWindow(dis),0,0,200,300,5,white,black);

XSetStandardProperties(dis,win,"My Windowww", "Hi", None, NULL , 0 , NULL);
XSelectInput(dis,win,ExposureMask|ButtonPressMask|KeyPressMask);

gc = XCreateGC(dis,win,0,0);

XSetBackground(dis,gc,white);
XSetForeground(dis,gc,black);

XClearWindow(dis,win);
XMapRaised(dis,win);

//while (1);

Window root_win, parent_win, *children_win;
unsigned int nchildren_win;
XQueryTree(dis , win , &root_win , &parent_win , &children_win , &nchildren_win);
printf("root %p , parent %p , nchild %d\n" , root_win , parent_win , nchildren_win);

int i;

XQueryTree(dis , root_win , &root_win , &parent_win , &children_win , &nchildren_win);
printf("root %p , parent %p , nchild %d\n" , root_win , parent_win , nchildren_win);

for (i = 0 ; i < nchildren_win ; i++) {
	int status, status2;
	XTextProperty wmName;
	char **list;
	char *title;
	int j,k;

	status = XGetWMName(dis , children_win[i] , &wmName);
	if ((status) && (wmName.value) && (wmName.nitems)) {
		status2 = XmbTextPropertyToTextList(dis , &wmName, &list, &j);	
		if ((status2 >= Success) && (j) && (*list))
		  printf("INFO - ");
		for (k = 0 ; k < j ; k++)
		  printf("[%s] ",(char *)strdup(list[k]));
		XFetchName(dis , children_win[i] , &title);
		  printf(" <%s> " , title);
		XSync(dis , 1);
		if (j > 0)
		  printf("\n");
	}
}
XEvent event;
KeySym key;
char text[255];

while (1) {
	XNextEvent(dis , &event);
}
}
