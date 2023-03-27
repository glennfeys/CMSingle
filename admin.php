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

/****************************************************************************** 
* Function: upload_file()
* Description: This function is used to upload a file to the server.
* Parameters: None
* Return: None
*******************************************************************************/

function upload_file(){
    // If file is uploaded
    if (isset($_FILES["fileToUpload"])) {
        $target_dir = UPLOAD_DIR;
        if (!file_exists($target_dir)) {
            mkdir($target_dir);
        }

        if($_FILES["fileToUpload"]["error"] !== UPLOAD_ERR_OK) {
            echo "Invalid upload";
        } else {
            $target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);

            // Check if image file is a actual image or fake image
            if(getimagesize($_FILES["fileToUpload"]["tmp_name"]) === false) {
                echo "File is not an image.";
            } else {
                // Check if file already exists
                if (file_exists($target_file)) {
                    echo "Sorry, file already exists.";
                // if everything is ok, try to upload file
                } else {
                    if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
                        echo "Your file was successfully uploaded";
                    } else {
                        echo "Sorry, there was an error uploading your file.";
                    }
                }
            }
        }
    }
}

/**
 * This function removes a file from the server.
 *
 * @return void
 */
function remove_file() {
    //remove image
    if (isset($_POST["fileDelete"])) {
        $file = get_safe_path($_POST["fileDelete"]);
        if(is_file($file)) {
            unlink($file);
            echo "successfully deleted file";
        }
    }
}

/**
 * This function retrieves a code snippet from a file specified in the $_POST["snippet_file"] parameter and outputs it to the browser.
 * 
 * @return void
 */
function get_snippet() {
    // Remove image
    if (isset($_POST["snippet_file"])) {
        $file = get_safe_path($_POST["snippet_file"]);
        if(is_file($file)) {
            $snippet = file_get_contents($file);
            echo $snippet;
        }
    }
}

/**
 * Saves the content of a file after editing.
 *
 * This function is executed when 'Save' is pressed and will set the value to the edited value.
 * It takes the file path and content as input from the $_POST array and saves the edited content to the file.
 * It also removes the editable content tags, inserted JavaScript and fills PHP tags.
 *
 * @return void
 */
function save(){
    if (isset($_POST["file"]) && isset($_POST["content"])) {
        $file = get_safe_path($_POST["file"]); // Get the safe path of the file
        $content = '<!doctype html><html>'.$_POST["content"].'</html>'; // Get the edited content

        $content = fillPhp($file, $content); // Fill PHP tags in the content

        // Remove the editable content tags
        $content = str_replace('contenteditable="true"', '', $content);

        // Remove the inserted JavaScript
        $pattern1 = '/<!--CMSingleBegin-->.*<!--CMSingleEnd-->/s';
        $pattern2 = '/^.*<!--CMSingleEnd-->/s';
        $pattern3 = '/<!--CMSingleBegin-->.*$/s';
        $replacement = '';
        $content =  preg_replace($pattern1, $replacement, $content);
        $content =  preg_replace($pattern2, $replacement, $content);
        $content =  preg_replace($pattern3, $replacement, $content);

        // Write edited content to file
        $fp = fopen($file, 'w') or die("Unable to open file!");
        fwrite($fp, $content);
        fclose($fp);
    }
}

/**
 * Reloads the current page or navigates to a new page if specified by the user.
 * 
 * @global string $currentFile - the current file being displayed
 * 
 * @return void
 */
function reload(){
    global $currentFile;

    // if the user specified a page to go to, set it here
    if (isset($_POST["goto"])) {
        $currentFile = get_safe_path($_POST["goto"]);
        if(!file_exists($currentFile)) {
            $currentFile = "";
        }
        fillContent($currentFile);
        echo "reload";
    }
}


/**
 * Fills admin content with the content of the given page with the editor inserted into it
 *
 * @param string $currentFile The path to the file to be loaded into the admin content
 * @return void
 */
function fillContent($currentFile='') {
    // scan directory for html/php files and set current file if this hasn't happened already
    $webpages = getWebpages();

    //if we haven't chosen a current page yet choose the first page
    if ($currentFile === '') {
        if (count($webpages) === 0) {
            die("no webpages found!");
        }
        $currentFile = $webpages[0];
    }

    //get content of current file
    $content = file_get_contents($currentFile);
    if($content === false) {
        die("Unable to open file!");
    }

    //add contenteditable property

    $content = str_replace('<?php', '<!--CMSingle_PHP_BEGIN--><?php', $content);
    $content = str_replace('?>', '?><!--CMSingle_PHP_END-->', $content);


    //make links for all webpages
    $links = '';
    foreach ($webpages as $page) {
        $links .= '<option value="'.$page.'">'.$page.'</option>';
    }

    function getDirContents($path) {
        $files = [];

        if (file_exists($path)) {
            $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
            foreach ($rii as $file)
                if (!$file->isDir())
                    $files[] = $file->getPathname();
        }

        return $files;
    }

    $images = getDirContents('.');

    $options = '';
    foreach ($images as $image) {
        if (@getimagesize($image)) {
            //echo $image;
            $image = str_replace('\\', '/', $image);
            //echo $image;
            $options .= '<option value="'.$image.'">'.$image.'</option>';
        }
    }

    $snippets = getDirContents(SNIPPET_DIR);

    $snippet_options = '';
    foreach ($snippets as $snippet) {
        if (is_readable($snippet)) {
            $snippet_options .= '<option value="'.$snippet.'">'.$snippet.'</option>';
        }
    }

    $additionalContent = '
        <!--CMSingleBegin-->
        <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.1/css/all.css" integrity="sha384-fnmOCqbTlWIlj8LyTjo7mOUStjsKC4pOpQbqyi7RrhN7udi9RwhKkMHpvLbHG9Sr" crossorigin="anonymous">
        <style type="text/css" scoped>
        select {
            width:100%; 
            overflow:hidden; 
            white-space:nowrap; 
            text-overflow:ellipsis;
        }
        select option {
            width:100px;
            text-overflow:ellipsis;
            overflow:hidden;
        }
        
        </style>

        <!-- Modal -->
        <div id="exampleModal" contenteditable="false" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
          <div class="modal-dialog" role="document">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Change image to ...</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <div class="modal-body">
                <select id="imgselect" onchange="setPreview()">'.$options.'</select>
                <br>
                <img id="previewImg" style="width: 100%; margin: 20px auto;" src=""/>
              </div>
              <div class="modal-footer">
                <button id="closeMyModal" type="button" class="btn btn-secondary" data-dismiss="

/**
 * This function takes in a file path and content string and replaces any PHP scripts generated by the PHP scripts back to the initial PHP scripts so that the file is the same as the original.
 *
 * @param string $file The file path to the PHP file
 * @param string $content The content of the PHP file
 * @return string The new content with the PHP scripts replaced
 */
function fillPhp($file, $content) {
    // Get the contents of the file
    $fileCont = file_get_contents($file);
    
    // Find all PHP scripts generated by the PHP scripts
    preg_match_all('/<\?.*\?>/Us', $fileCont, $matches1);

    // Loop through each PHP script and replace it in the content
    for ($i=0; $i<count($matches1[0]); $i++) {
        if ($i === 0 && strpos($content, '<!--CMSingle_PHP_BEGIN-->') > strpos($content, '<!--CMSingle_PHP_END-->')) {
            // Replace the first PHP script in the content
            $content = preg_replace('/^.*<!--CMSingle_PHP_END-->/Us', $matches1[0][$i], $content);
        } else if ($i === count($matches1[0])-1 && !strpos($content, '<!--CMSingle_PHP_END-->') && strpos($content, '<!--CMSingle_PHP_BEGIN-->')) {
            // Replace the last PHP script in the content
            $content = preg_replace('/<!--CMSingle_PHP_BEGIN-->.*$/Us', $matches1[0][$i], $content);
        } else {
            // Replace any PHP script in the content
            // TODO when there are slashes in matches1 or other special chars, things can break
            $content = preg_replace('/(^.*)<!--CMSingle_PHP_BEGIN-->.*<!--CMSingle_PHP_END-->/Us', '$1 '.$matches1[0][$i], $content);
        }
    }

    // Return the new content with the PHP scripts replaced
    return $content;
}

/**
 * Returns the CSRF token.
 *
 * @return string Returns the CSRF token as a string.
 */
function csrf_token(): string {
    return isset($_SESSION["csrf"]) ? $_SESSION["csrf"] : "";
}

/**
 * Output CSRF field.
 *
 * This function outputs a hidden input field with the name "_token" and value set to the result of the function csrf_token().
 *
 * @return void
 */
function csrf_field() {
    echo '<input type="hidden" name="_token" value="' . csrf_token() . '">';
}

/**
 * This function takes a string path as input and returns a safe path by replacing any forward slashes or backslashes with the appropriate directory separator.
 * It also removes any unnecessary or potentially harmful parts of the path such as empty strings, dots, and double dots.
 *
 * @param string $path The original path to be sanitized
 * @return string The sanitized path
 */
function get_safe_path(string $path): string {
    $path = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $path); // Replace forward slashes and backslashes with directory separator
    $parts = explode(DIRECTORY_SEPARATOR, $path); // Split the path into an array of parts

    $newParts = [];
    foreach($parts as $part) {
        if($part === "" || $part === ".") // Remove empty strings and dots
            continue;

        if($part === "..") // Handle double dots
            array_pop($newParts);
        else
            array_push($newParts, $part);
    }

    return "./" . implode(DIRECTORY_SEPARATOR, $newParts); // Return the sanitized path
}

/**
 * Checks authentication by verifying the CSRF token and password.
 * If authentication fails, displays a login form.
 *
 * @return void
 */
function check_auth() {
    // Check if CSRF token is correct
    if(!in_array($_SERVER["REQUEST_METHOD"], ["GET", "HEAD"])) {
        if(!isset($_POST["_token"]) || !is_string($_POST["_token"]) || !hash_equals(csrf_token(), $_POST["_token"])) {
            die("wrong csrf token");
        }
    }

    $password = "";
    if (isset($_SESSION["password"])) {
        $password = $_SESSION["password"];
    } elseif (isset($_POST["password"]) && is_string($_POST["password"])) {
        $password = $_POST["password"];
    }

    // check if there is a session/correct password
    if(password_verify($password, PASSWORD)) {
        $_SESSION["password"] = $password;
    } else {
        unset($_SESSION["password"]);
        $_SESSION["csrf"] = base64_encode(random_bytes(50));
        if(file_exists(ADMIN_CONTENT)) unlink(ADMIN_CONTENT);
    ?>
    <!doctype html>
    <html>
        <head>
            <meta charset="utf-8">
            <title>CMSingle</title>
        </head>
        <body>
            <?php
            if(isset($_POST["password"]) || isset($_SESSION["password"])) {
                echo "Invalid credentials<br>";
            }
            ?>
            <form method="post" action="admin.php">
                <?php csrf_field(); ?>
                <div style="margin:auto; margin-top:100px; text-align:center; width:280px; padding:20px; border:1px solid black; border-radius: 15px">
                    <div style="margin:auto; text-align:center; width:250px;">
                        <h2>CMSingle</h2>
                        <hr>
                        <b>Password: </b> &nbsp;&nbsp; <input style="width:155px" type="password" name="password"><br>
                        <input style="width:100%; margin:5px 0;" type="submit" value="log in">
                    </div>
                </div>
                
            </form>
        </body>
    </html>
    <?php
        die();
    }
}

/**
 * Returns an array of webpages in the current directory.
 *
 * @global array $excludedFiles Array of files to exclude from the list of webpages.
 * @global string $currentFile The name of the current webpage.
 *
 * @return array An array of webpages in the current directory.
 */
function getWebpages(){
    global $excludedFiles, $currentFile;
    $webpages = [];
    foreach (scandir('.') as $file) {
        if (!in_array($file, $excludedFiles) && (substr($file, -4) === ".php" || substr($file, -5) === ".html")) {
            if ($currentFile === '' && ($file === "index.php" || $file === "index.html")) {
                array_unshift($webpages, $file);
            } else {
                $webpages[] = $file;
            }
        }
    }
    return $webpages;
}
