# Batch Create

**INACTIVE NOTICE: This plugin is unsupported by WPMUDEV, we've published it here for those technical types who might want to fork and maintain it for their needs.**

## Translations

Translation files can be found at https://github.com/wpmudev/translations

## Batch Create lets you create hundreds or thousands of sites and users all at once.

Whether you're setting up accounts for an entire University or creating a huge network on a heap of different topics batch create can save you countless hours.

### Speed and Efficiency

There are many reasons to automate the creation of sites and users, and for adding lots of users to a site quickly – and this plugin serves precisely that purpose.

### Activate and Go

Simply install and activate the plugin and 'Batch Create' will be added to the network admin 'Settings' menu. 

![Settings > Batch Create in network admin dashboard](http://premium.wpmudev.org/wp-content/uploads/2011/04/bcreate61.jpg)

 Easy access from network admin

 Now Super Admin users can batch create new sites, new users and add users to existing sites by simply uploading a .xls or .csv file. 

![Uploading to batch create](http://premium.wpmudev.org/wp-content/uploads/2011/04/bcreate62.jpg)

 Just choose a file and upload

 Creating the batch file is as simple as downloading the .xls template, following the included instructions and uploading. Choose URLs, site titles, usernames, user roles and user passwords. 

![Using the Batch Create template](http://premium.wpmudev.org/wp-content/uploads/2011/04/bcreate63.jpg)

 Includes easy-to-follow template

 Bulk create sites and users quickly and efficiently using Batch Create.

## Usage

Start by reading [Installing plugins](https://premium.wpmudev.org/wpmu-manual/using-regular-plugins-on-wpmu/) section in our comprehensive [WordPress and WordPress Multisite Manual](https://premium.wpmudev.org/wpmu-manual/) if you are new to WordPress Multisite.

### To install:

1\. Download the plugin file 2.Unzip the file into a folder on your hard drive 3\. Upload **/batch-create/** folder to **/wp-content/plugins/** folder on your site 4\. Visit **Network Admin -> Plugins** and **Network Activate** it there. _Note: If you have an older version of the plugin installed in /mu-plugins/ please delete it._ **That's it! No configuration necessary!**

### To Use:

A new menu item called **Batch Create** should appear under the **Settings** navigation menu in the network admin dashboard once the plugin has been network activated. It is designed for quickly creating sites and/or usernames or adding users to an existing site in batches of 10's, 100's or 1000's by uploading a .xls file or .csv text file. 1\. Go to **Settings > Batch Create** in the network admin dashboard2\. Download the template file by clicking on the 'this .xls' link 3\. Follow the instructions in the header row of the .xls template file to replace your site and users details with the provided examples. 4\. Save your batch file as Excel 97-2003 or a .csv file. 5\. Now upload and process your batch file. 

![Using Batch Create](https://premium.wpmudev.org/wp-content/uploads/2011/04/bcreate63.jpg)

### Some Facts About Batch Create

*   The easiest option for creating your batch create file is to use Excel and save it as a Excel 97-2003 Workbook
*   It needs to be written in this order --Site Name,Site Title, username, password, user email and user role
*   Each new user must be on a separate row and each of a users details in a new column
*   WordPress doesn't allow multiple user to have the same email address
*   When the system encounters an error creating a user in the batch file, the error log will be updated and all other processing will stop. That way the admin can go through the batch file and correct the problematic entry

**Site Name**

*   The name of the site you want created or the user added to (if that site already exists).
*   If you do not want the user to have a site, please set this to 'null' without the quotation marks.
*   This will be part of the URL for the site (ex. site-name.myblogs.org or myblogs.org/site-name)
*   You can only use lowercase letters, numbers and dashes in site URLs (site address). Dots, underscore, special characters and spaces in site URLs aren't allowed.

**Site Title**

*   The title of the site.
*   This can be changed later in **Settings > General** in the site admin dashboard

**User Name**

*   The login or username of the user.
*   No spaces allowed. This can't be changed later.
*   You can only use lowercase letters and numbers in usernames. Dots, underscore, dashes, special characters and spaces in usernames aren't allowed and can result in problems with usernames.

**User password**

*   If you would like a password auto-generated please set this to 'null' without the quotation marks.
*   No spaces are allowed in passwords
*   The user will get an email with the password.

**User Email **

*   You must provide a valid email for each user as these are required for functions like password resets, comment notification and deleting sites.
*   WordPress doesn’t allow multiple users to have the same email address. This means that the email addresses for new users in the batch file should be unique not only in the batch file, but across system too.
*   Spam filters, especially strict ones for institutional email addresses, may well block username and login information from reaching users. In this case you should recommend users use free webmail accounts that won’t block the emails (such as gmail.com, hotmail.com or mail.yahoo.com)

**User Role**

*   The user role (when the user is added to an existing site).
*   Role can be set to subscriber, contributor, author, editor, or administrator.
*   If user role isn't specified they are automatically added as an administrator.
*   If a new site is being created and the assigned user's role isn't administrator the Super Admin will be assigned to the site as an adminstrator

**Here are some examples of how you can use batch create:** 

![Using Batch Create](https://premium.wpmudev.org/wp-content/uploads/2011/04/batchcreateex.jpg)

