# CMSingle

CMSingle is an open source CMS that uses only a single php file. No database is required. It can only be used with html projects and partially with php projects. But no php will be executed in editing mode (yet).

## Installation

1. Add admin.php to the project directory where you want to edit your HTML files.
2. Configure a password by changing `define("PASSWORD", 'YOUR HASHED PASSWORD HERE');`. (Keep the single quotes.)

   To create a hashed password, execute this from the command line:
   
   `php -r 'echo password_hash("YOUR PASSWORD HERE", PASSWORD_DEFAULT);'`
3. Profit

This program requires PHP 7 or higher.

## Usage

Go to {your website here}/admin.php

here you will go to your homepage and you will have an aditional menu on the bottom right showing all webpages in the directory and a save button. If you click on a page you will go to that page in the edit modus. when you press save, the page will be automatically saved in the website.

## Contributing
Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.

## License
[MIT](https://choosealicense.com/licenses/mit/)
