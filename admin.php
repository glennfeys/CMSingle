<?php
/****************************************************************************** */
// Configuration section, you can make changes here.
define("PASSWORD", 'ADD PASSWORD HERE');
define("UPLOAD_DIR", "img/");
define("SNIPPET_DIR", "cmsnippets");
define("ADMIN_CONTENT", "adminContent.php");

$excludedFiles = [
    "admin.php",
    "header.php",
    "footer.php",
    "mail.php",
    ADMIN_CONTENT
];

// End of configuration section, don't make changes below.
/****************************************************************************** */


session_start();
// check if user has correct csrf token or let user log in
check_auth();

$currentFile = '';

if(isset($_POST["task"])) {
    switch ($_POST["task"]) {
        case 'upload-file':
            upload_file();
            break;

        case 'remove-file':
            remove_file();
            break;

        case 'save':
            save();
            break;

        case "reload":
            reload();
            break;

        case "get-snippet":
            get_snippet();
            break;
        
        default:
            echo "Error: We could not find the task you are trying to do";
            break;
    }
    die();
}

if(!file_exists(ADMIN_CONTENT)) {
    fillContent('');
}

try {
    if (!include ADMIN_CONTENT) {
        unlink(ADMIN_CONTENT);
        echo "Error file couldn't load";
    }
} catch(\Throwable $ex) {
    echo "Error file couldn't load";
    unlink(ADMIN_CONTENT);
}

tes
tes
tes
tes
tes

tes
tes
tes
tes
tes
tes
tes