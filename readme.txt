=== Pre Date Future Post ===
Contributors: brofield
Tags: postdate, posts, date, predate, autopost
Requires at least: 2.3.0
Tested up to: 3.4.1
Stable tag: 1.3

Allows you to set posts to appear automatically in the future, but have a post date in the past.

== Description ==

Allows you to set posts to appear automatically in the future, but have a post date in the past.
For instance, your blog may be a diary and you need to catch up on a few entries.
You write 5 entries that cover the last couple of weeks, but want to release the
new entries in a trickle of one per day instead of just publishing them all at once.
That is where this plugin helps.

How to use it:

* Write the post and set the post date to the actual date in the past that the
	post was for. 
* Set the post status to "Pending Review" and save it.
* A new section will appear below the post text called "Make Post Public Date".
* Enable it, and now set the date and time for the post to automatically become 
    public. 
* Save the post again.
* That's it. At the date and time that you set in "Make Post Public Date", the
	post will automatically be changed from "Pending Review" to "Published" and
	it will appear on your blog with a post date in the past.

Notes:

* A new column will be added to the post list showing the future date that each 
    "Pending Review" post will be made public.
* All times and dates uses the current timezone as set for your blog. 

== Installation ==

This section describes how to install the plugin and get it working.

1. Download and extract the contents of the plugin zip file
1. Upload it to the '/wp-content/plugins/' directory. Note that like in the zip file,
   all files should be placed in a sub-directory of 'pre-date-future-post'.
1. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= Can I use a post status other than "Pending Review"? =

Not at the moment. If you can tell me how to add a custom post status then I 
am happy to add that functionality. If you need to use "Pending Review" yourself
then you probably can't use this plugin.

= Why can't I set the minute as well as the hour for posting? =

Because I said so.

= Why doesn't the post show immediately it is scheduled to appear? =

Perhaps because:

* WP cron hasn't yet run the task. Wait until a minute after the hour and
  refresh the blog main page.
* Something caused the WP cron process to skip the task. Wait an hour.
* If you have changed the timezone for your blog then you may need to
  deactivate and reactivate the plugin to reset the cron timer.

= Can I see what posts have been processed and when? =

Yes. It writes a log of activation, deactivation and posting of entries.
See the file "pre-date-future-post.php.log" created in the same directory
as the plugin PHP file.


== Credits ==

Much of the code from the Post Expirator plugin by axelseaa was filched
but has since been modified beyond much recognition.

== Changelog ==

* Version 1.3 : Fixed the installation instructions so that it now works 
                with the automatic update system.
* Version 1.2 : Looking stable
* Version 1.1 : I like changing the numbers
* Version 1.0 : first public release