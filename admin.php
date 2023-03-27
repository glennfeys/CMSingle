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

/**
 * upload_file function
 * 
 * This function uploads a file to the server and checks if it is a valid image file.
 * 
 * @return void
 */
function upload_file(){
    // If file is uploaded
    if (isset($_FILES["fileToUpload"])) {
        $target_dir = UPLOAD_DIR;
        // Create directory if it doesn't exist
        if (!file_exists($target_dir)) {
            mkdir($target_dir);
        }

        // Check for upload errors
        if($_FILES["fileToUpload"]["error"] !== UPLOAD_ERR_OK) {
            echo "Invalid upload";
        } else {
            $target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);

            // Check if image file is a actual image or fake image
            if(getimagesize($_FILES["fileToUpload"]["tmp_name"]) === false) {
                echo "File is not an image.";
            } else {
                // Check if file already exist
/**
 * Removes a file from the server.
 *
 * This function removes a file from the server when the "fileDelete" parameter is set in the $_POST array.
 * The function first checks if the file exists and is a valid file. If it is, the function deletes the file using the unlink() function.
 * If the file is deleted successfully, the function will output "successfully deleted file".
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

/**
 * This function retrieves a code snippet from a file and displays it.
 * 
 * @return void
 */
function get_snippet() {
    //remove image
    if (isset($_POST["snippet_file"])) {
        $file = get_safe_path($_POST["snippet_file"]);
        if(is_file($file)) {
            $snippet = file_get_contents($file);
            echo $snippet;
        }
    }

/**
 * This function is responsible for saving the edited content to file
 * when the 'Save' button is pressed.
 *
 * @return void
 */
function save(){
    //this is executed when 'Save' is pressed and will set the value to the editted value
    if (isset($_POST["file"]) && isset($_POST["content"])) {
        $file = get_safe_path($_POST["file"]);
        $content = '<!doctype html><html>'.$_POST["content"].'</html>';

        $content = fillPhp($file, $content);

        //remove the editable content tags
        $content = str_replace('contenteditable="true"', '', $content);
        $content = str_replace('<!--?php', '<?php', $content);
        $content = str_replace('?-->', '?>', $content);

        //remove the inserted js
        $pattern1 = '/<!--CMSingleBegin-->.*<!--CMSingleEnd-->/s';
      
/**
 * Reloads the current page.
 * 
 * This function sets the current file to the page specified by the user if provided. If the specified page does not exist, the current file is set to an empty string. The function then fills the content of the current file and echoes "reload".
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


/**
 * Adds admin content to the given page with an editor inserted into it
 *
 * @param string $currentFile The path to the current file
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
  
/**
 * This function replaces the PHP code generated by scripts with the original PHP code from the file.
 *
 * @param string $file The path to the file containing the original PHP code.
 * @param string $content The content with generated PHP code to be replaced.
 * @return string The new content with replaced PHP code.
 */
function fillPhp($file, $content) {
    // Get the content of the file containing the original PHP code.
    $fileCont = file_get_contents($file);

    // Find all generated PHP code blocks in the file content.
    preg_match_all('/<\?.*\?>/Us', $fileCont, $matches1);

    // Loop through all the generated PHP code blocks and replace them with the original PHP code.
    for ($i=0; $i<count($matches1[0]); $i++) {
        if ($i === 0 && strpos($content, '<!--CMSingle_PHP_BEGIN-->') > strpos($content, '
/**
 * Returns the CSRF token.
 *
 * @return string The CSRF token.
 */
function csrf_token(): string {
    return isset($_SESSION["csrf"]) ? $_SESSION["csrf"] : "";

/**
 * Output CSRF field.
 *
 * This function outputs a hidden input field with the name "_token" and the value of the CSRF token.
 * This field is used to protect against cross-site request forgery (CSRF) attacks.
 *
 * @return void
 */
function csrf_field() {
    echo '<input type="hidden" name="_token" value="' . csrf_token() . '">';

/**
 * Gets safe path from user input
 *
 * This function takes a string path as input and returns a safe path by replacing all forward slashes and backslashes with the correct directory separator for the current operating system. It also removes any unnecessary path components such as "." and "..".
 *
 * @param string $path The path to sanitize
 * @return string The sanitized path
 */
function get_safe_path(string $path): string {
    $path = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $path);
    $parts = explode(DIRECTORY_SEPARATOR, $path);

    $newParts = [];
    foreach($parts as $part) {
        if($part === "" || $part === ".")
            continue;

        if($part === "..")
            array_pop($newParts);
        else
            array_push($newParts, $part);
    }

    return "./" . implode(DIRECTORY_SEPARATOR, $newParts);

/**
 * Check authentication and CSRF token.
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
        $_SESSION["csrf"] = base64_encode(random_byte
/**
 * Returns an array of all webpages in the current directory.
 * Excludes files listed in the global $excludedFiles array.
 * If $currentFile is empty, the index file(s) will be listed first.
 *
 * @return array
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
