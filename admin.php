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

/****************************************************************************** */

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
                        echo "Your file was succesfully uploaded";
                    } else {
                        echo "Sorry, there was an error uploading your file.";
                    }
                }
            }
        }
    }
}

function remove_file() {
    //remove image
    if (isset($_POST["fileDelete"])) {
        $file = get_safe_path($_POST["fileDelete"]);
        if(is_file($file)) {
            unlink($file);
            echo "succesfully deleted file";
        }
    }
}

function get_snippet() {
    //remove image
    if (isset($_POST["snippet_file"])) {
        $file = get_safe_path($_POST["snippet_file"]);
        if(is_file($file)) {
            $snippet = file_get_contents($file);
            echo $snippet;
        }
    }
}

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
        $pattern2 = '/^.*<!--CMSingleEnd-->/s';
        $pattern3 = '/<!--CMSingleBegin-->.*$/s';
        $replacement = '';
        $content =  preg_replace($pattern1, $replacement, $content);
        $content =  preg_replace($pattern2, $replacement, $content);
        $content =  preg_replace($pattern3, $replacement, $content);

        //write editted content to file
        $fp = fopen($file, 'w') or die("Unable to open file!");
        fwrite($fp, $content);
        fclose($fp);
    }
}

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
 * @param string $currentFile
 */
function fillContent($currentFile='') {

    // scan directory for html/php files and set current file if this hasnt happened already
    $webpages = get_webpages();

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
                <button id="closeMyModal" type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button id="setI" type="button" class="btn btn-primary" onclick="setImg()">Change Image</button>
                <button id="removeI" type="button" class="btn btn-primary" onclick="removeImg()">Remove Image</button>
              </div>
            </div>
          </div>
        </div>

        <div id="editBar" contenteditable="false" style="position:fixed; bottom:0; right:0; z-index: 9; background-color: #f7faff; box-shadow: inset -4px -5px 34px -6px rgba(0,0,0,0.67); max-height: 1000px; transition: max-height 1s;">
            <div id="toggle" style="width:100%; padding:10px; background-color: #c4c7cc;text-align: center;" onclick="toggleV()"><i id="toggleBtn" class="fas fa-chevron-down"></i></div>
            <div style="margin:10px;">
                <h3>Editor</h3>
                <button  onclick="document.execCommand(\'bold\',false,null);"><i class="fas fa-bold"></i></button>
                <button  onclick="document.execCommand(\'italic\',false,null);"><i class="fas fa-italic"></i></button>
                <button  onclick="document.execCommand(\'underline\',false,null);"><i class="fas fa-underline"></i></button>
                <button  onclick="insertImg()"><i class="fas fa-image"></i>+</button>
                <br>
                <button  onclick="document.execCommand(\'decreaseFontSize\',false,null);">A<i class="fas fa-long-arrow-alt-down"></i></button>
                <button  onclick="document.execCommand(\'increaseFontSize\',false,null);">A<i class="fas fa-long-arrow-alt-up"></i></button>
                <br>
                <form id="uploadForm" action="admin.php" method="post" enctype="multipart/form-data">
                    <input type="file" name="fileToUpload" id="fileToUpload" onchange="submitUpload()">
                </form>
                <button data-toggle="modal" data-target="#exampleModal"  onclick="callRemove()">Remove Image</button>
                <button class="d-none" id="editImageBtn" data-toggle="modal" data-target="#exampleModal"></button>
                <input style="width:100%;" type="button" id="saveBtn" value="Save" onclick="saveFile()"/>
                <br>
                <h4 style="margin:5px 0">Webpages</h4>
                <select onchange="goTo(this.value)" style="width:100%; margin:5px 0">
                    <option disabled selected value> -- select a page -- </option>
                    '.$links.'
                </select>
                <h4 style="margin:5px 0">Snippets</h4>
                <select id="snippet-select" style="width:88%; margin:5px 0">
                    <option disabled selected value> -- select a snippet -- </option>
                    '.$snippet_options.'
                </select>
                <button style="width:10%;"  onclick="insertSnippet()">+</button>
                <br>
            </div>
        </div>
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous">

        <script type="text/javascript" src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
        <script>
            var curPage = "'.$currentFile.'"


            function saveFile() {

                const url = "admin.php"
                const formData = new FormData()
                formData.append("task", "save")
                formData.append("file", curPage)
                formData.append("content", document.documentElement.innerHTML)
                formData.append("_token", "'.csrf_token().'")

                fetch(url, {
                    method: "POST",
                    body: formData,
                })
                .then(response => response.text())
                .then(response => {
                    alert("succesfully saved") //TODO
                })
            }
            function goTo(file) {
                const url = "admin.php"
                const formData = new FormData()
                formData.append("goto", file)
                formData.append("task", "reload")
                formData.append("_token", "'.csrf_token().'")

                fetch(url, {
                    method: "POST",
                    body: formData,
                })
                .then(response => response.text())
                .then(response => {
                    if (response === "reload") {
                        location.reload();
                    }
                })
            }

            var toggled = false;

            function toggleV() {
                if (toggled) {
                    document.getElementById("editBar").style.maxHeight = "35px";
                    document.getElementById("toggleBtn").classList.add("fa-chevron-up");
                    document.getElementById("toggleBtn").classList.remove("fa-chevron-down");
                } else {
                    document.getElementById("editBar").style.maxHeight = "1000px";
                    document.getElementById("toggleBtn").classList.add("fa-chevron-down");
                    document.getElementById("toggleBtn").classList.remove("fa-chevron-up");
                }
                toggled = !toggled;
            }
            
            function submitUpload() {
                const url = "admin.php"
                const form = document.querySelector("#uploadForm")
                const formData = new FormData()
                let image = document.getElementById("fileToUpload").files[0];
                formData.append("task", "upload-file")
                formData.append("fileToUpload", image)
                formData.append("_token", "'.csrf_token().'")

                fetch(url, {
                    method: "POST",
                    body: formData,
                })
                .then(response => response.text())
                .then(response => {
                    alert(response);
                    if (response === "Your file was succesfully uploaded") {
                        let x = document.getElementById("imgselect");
                        let option = document.createElement("option");
                        option.text = "'.UPLOAD_DIR.'"+image.name;
                        option.value = "'.UPLOAD_DIR.'"+image.name;
                        x.add(option);
                    }
                });
            }

            function removeImg() {
                let e = document.getElementById("imgselect");
                let value = e.options[e.selectedIndex].value;
                const formData = new FormData();
                formData.append("task", "remove-file");
                formData.append("_token", "'.csrf_token().'");
                formData.append("fileDelete", value);
                fetch("admin.php", {
                    method: "POST",
                    body: formData,
                })
                .then(response => response.text())
                .then(response => {
                    if (response === "succesfully deleted file") {
                        e.remove(e.selectedIndex);
                    }
                    alert(response);
                });
            }

            var imgEditting;

            $(window).on("load", function() {
                document.body.contentEditable = true
                $("img").click(function(){
                    imgEditting = this;
                    document.getElementById("setI").style.display = "block";
                    document.getElementById("removeI").style.display = "none";
                    $("#editImageBtn").click();
                });
            });

            function addListeners() {
                $("img").click(function(){
                    imgEditting = this;
                    $("#exampleModal").modal("show");
                    document.getElementById("setI").style.display = "block";
                    document.getElementById("removeI").style.display = "none";
                });
            }

            function callRemove() {
                document.getElementById("setI").style.display = "none";
                document.getElementById("removeI").style.display = "block";
            }
            
            function setImg() {
                let e = document.getElementById("imgselect");
                let value = e.options[e.selectedIndex].value;

                imgEditting.src = value;
                $("#closeMyModal").click();
            }

            function insertSnippet() {
                let e = document.getElementById("snippet-select");
                let snippetPath = e.options[e.selectedIndex].value;
                const formData = new FormData();
                formData.append("task", "get-snippet");
                formData.append("_token", "'.csrf_token().'");
                formData.append("snippet_file", snippetPath);
                fetch("admin.php", {
                    method: "POST",
                    body: formData,
                })
                .then(response => response.text())
                .then(response => {
                    pasteHtmlAtCaret(response)
                });
                addListeners()
            }

            function insertImg() {
                pasteHtmlAtCaret("<img src=\'\' alt=\'click to select picture\'>")
                addListeners()
            }

            function pasteHtmlAtCaret(html) {
                var sel, range;
                if (window.getSelection) {
                    // IE9 and non-IE
                    sel = window.getSelection();
                    if (sel.getRangeAt && sel.rangeCount) {
                        range = sel.getRangeAt(0);
                        range.deleteContents();
            
                        // Range.createContextualFragment() would be useful here but is
                        // only relatively recently standardized and is not supported in
                        // some browsers (IE9, for one)
                        var el = document.createElement("div");
                        el.innerHTML = html;
                        var frag = document.createDocumentFragment(), node, lastNode;
                        while ( (node = el.firstChild) ) {
                            lastNode = frag.appendChild(node);
                        }
                        range.insertNode(frag);
            
                        // Preserve the selection
                        if (lastNode) {
                            range = range.cloneRange();
                            range.setStartAfter(lastNode);
                            range.collapse(true);
                            sel.removeAllRanges();
                            sel.addRange(range);
                        }
                    }
                } else if (document.selection && document.selection.type != "Control") {
                    // IE < 9
                    document.selection.createRange().pasteHTML(html);
                }
            }

            function setPreview() {
                let e = document.getElementById("imgselect");
                let value = e.options[e.selectedIndex].value;
                let img = document.getElementById("previewImg");
                img.src = value;
            }

            var modal = document.getElementById("myModal");

            var span = document.getElementsByClassName("close")[0];

            span.onclick = function() {
            modal.style.display = "none";
            }

            window.onclick = function(event) {
                if (event.target == modal) {
                    modal.style.display = "none";
                }
            }
            
        </script>
        <!--CMSingleEnd-->
    ';

    // add save buton, links and scripts
    if (!preg_match('/(<body.*>)/Us', $content)) {
        if (!preg_match('/(^.*\?>)/Us', $content)) {
            $content = ''.$additionalContent.$content;
        } else {
            $content = preg_replace('/(^.*\?>)/Us', '$1 <!--BODYHERE-->', $content);
            $content = str_replace('<!--BODYHERE-->', $additionalContent, $content);
        }
    } else {
        $content = preg_replace('/(<body.*>)/Us', '$1'.$additionalContent, $content);
    }
    file_put_contents(ADMIN_CONTENT, $content);
}

/**
 * replaces the things generated by the php scripts back to the initial php scripts so the file is the same as the original
 *
 * @param string $currentFile
 */
function fillPhp($file, $content) {
    $fileCont = file_get_contents($file);
    preg_match_all('/<\?.*\?>/Us', $fileCont, $matches1);

    for ($i=0; $i<count($matches1[0]); $i++) {
        if ($i === 0 && strpos($content, '<!--CMSingle_PHP_BEGIN-->') > strpos($content, '<!--CMSingle_PHP_END-->')) {
            $content = preg_replace('/^.*<!--CMSingle_PHP_END-->/Us', $matches1[0][$i], $content);
        } else if ($i === count($matches1[0])-1 && !strpos($content, '<!--CMSingle_PHP_END-->') && strpos($content, '<!--CMSingle_PHP_BEGIN-->')) {
            $content = preg_replace('/<!--CMSingle_PHP_BEGIN-->.*$/Us', $matches1[0][$i], $content);
        } else {
            // TODO when there are slahes in matches1 or other special chars, things can brake
            $content = preg_replace('/(^.*)<!--CMSingle_PHP_BEGIN-->.*<!--CMSingle_PHP_END-->/Us', '$1 '.$matches1[0][$i], $content);
        }
    }

    return $content;
}

/**
 * Returns the CSRF token.
 *
 * @return string
 */
function csrf_token(): string {
    return isset($_SESSION["csrf"]) ? $_SESSION["csrf"] : "";
}

/**
 * Output CSRF field.
 *
 * @return void
 */
function csrf_field() {
    echo '<input type="hidden" name="_token" value="' . csrf_token() . '">';
}

/**
 * Gets safe path from user input
 *
 * @param string $path
 * @return string
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
}

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

function get_webpages(){
    global $excludedFiles, $currentFile;
    $webpages = [];
    foreach (scandir('.') as $file) {
        $len = strlen($file);
        if (!in_array($file, $excludedFiles) && (($len > 5 && substr($file, $len-4,4) === ".php") || ($len > 6 && substr($file, $len-5,5) === ".html"))) {
            if ($currentFile === '' && ($file === "index.php" || $file === "index.html")) {
                array_unshift($webpages, $file);
            } else {
                $webpages[] = $file;
            }
        }
    }
    return $webpages;
}




