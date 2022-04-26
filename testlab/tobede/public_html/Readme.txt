2021/4/23
onedefect.html 
	Original getDefect is called with the attachment is downloaded in the same time. However the attachment might be over 8MB, and the transmission will be eof before the transmission of data is really completed. See the #45047, there is a strdb.zip and the base64 encoded size is about 28MB.

	The new approaches:
	1. separate the attachment from defect itself. That is, getDefect with bDownloadAttachments false. Then call getAttachment if ["pDefect"]["reportedbylist"]["item"]["attachmentlist"]["item"] indicates there might be attachments.
	2. There seems a setting in IIS, by the support of Perforce,
  
==================================================================================
1) They should set their connection timeout to larger than the 2 minute default.
    https://www.iis.net/configreference/system.applicationhost/sites/sitedefaults/limits
2) They should set their Maximum Cache Response Size to larger than the default of 256K.
    https://technet.microsoft.com/en-us/library/cc771895(v=ws.10).aspx 
==================================================================================
	And with help from HQ IT on 4/23, the change on item 2 above seems work.

	3. For attachment that is not able to preview, the getAttachment should be postponed to user specifically click on Download in onedefect.html.

	4. In case, the read (or stream_get_contents) size is different from Content-Length of HTTP, an exception should be handled. For example, let user go to ALM Web interface instead.
