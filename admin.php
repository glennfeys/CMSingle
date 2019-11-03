<?php
// Configuration section, you can make changes here.
define("PASSWORD", '$2y$10$NlQgA/AZ4NzL0.nYQVjT.eKQTMRXpihnao/c/V1Frd4fm4w8t36zG');
define("UPLOAD_DIR", "img/");
define("ADMIN_CONTENT", "adminContent.php");
// End of configuration section, don't make changes below.

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

/******************************************************************************/

session_start();

// Check for CSRF
if(!in_array($_SERVER["REQUEST_METHOD"], ["GET", "HEAD"])) {
    if(!isset($_POST["_token"]) || !is_string($_POST["_token"]) || !hash_equals(csrf_token(), $_POST["_token"])) {
        die();
    }
}

$password = "";
if(isset($_SESSION["password"])) {
    $password = $_SESSION["password"];
} elseif(isset($_POST["password"]) && is_string($_POST["password"])) {
    $password = $_POST["password"];
}

if(password_verify($password, PASSWORD)) {
    $_SESSION["password"] = $password;
} else {
    unset($_SESSION["password"]);
    $_SESSION["csrf"] = base64_encode(random_bytes(50));
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
            Password: <input type="password" name="password"><br>
            <input type="submit" value="log in">
        </form>
    </body>
</html>
<?php
    fillContent('');
    die();
}

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

    die();
}

//remove image
if (isset($_POST["fileDelete"]) && is_file($_POST["fileDelete"])) {
    unlink($_POST["fileDelete"]);
    die("succesfully deleted file");
}

$currentFile = '';
$content = '';
$webpages = [];

//this is executed when 'Save' is pressed and will set the value to the editted value
if (isset($_POST["file"]) && isset($_POST["content"])) {
    $file = get_safe_path($_POST["file"]);
    $content = '<html>'.$_POST["content"].'</html>';

    $content = fillPhp($file, $content);

    //remove the editable content tags and restore the php tags
    $content = str_replace('contenteditable="true"', '', $content);

    //remove the inserted js
    $pattern = '/<!--CMSingleBegin-->.*<!--CMSingleEnd-->/s';
    $replacement = '';
    $content =  preg_replace($pattern, $replacement, $content);

    //write editted content to file
    $fp = fopen($file, 'w') or die("Unable to open file!");
    fwrite($fp, $content);
    fclose($fp);

    die();
}

// if the user specified a page to go to set it here
if (isset($_POST["goto"])) {
    $currentFile = get_safe_path($_POST["goto"]);
    if(!file_exists($currentFile)) { // TODO: notify user?
        $currentFile = "";
    }
    fillContent($currentFile);
    //echo $currentFile;
    die("reload");
}

/*
if ($currentFile === '') {
    fillContent($currentFile);
}
*/


function fillContent($currentFile='') {

    // scan directory for html/php files and set current file if this hasnt happened already
    foreach (scandir('.') as $file) {
        $len = strlen($file);
        if ($file !== 'admin.php' && (($len > 5 && substr($file, $len-4,4) === ".php") || ($len > 6 && substr($file, $len-5,5) === ".html"))) {
            $webpages[] = $file;
        }
        // set index file as current file
        if ($currentFile === '' && ($file === "index.php" || $file === "index.html")) {
            $currentFile = $file;
        }
    }

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
    $content = str_replace('<p', '<p contenteditable="true"', $content);
    $content = str_replace('<a', '<a contenteditable="true"', $content);
    $content = str_replace('<input', '<input contenteditable="true"', $content);
    $content = str_replace('<td', '<td contenteditable="true"', $content);
    $content = str_replace('<h1', '<h1 contenteditable="true"', $content);
    $content = str_replace('<h2', '<h2 contenteditable="true"', $content);
    $content = str_replace('<h3', '<h3 contenteditable="true"', $content);
    $content = str_replace('<h4', '<h4 contenteditable="true"', $content);
    $content = str_replace('<h5', '<h5 contenteditable="true"', $content);
    $content = str_replace('<h6', '<p6 contenteditable="true"', $content);

    $content = str_replace('<?', '<!--CMSinglePHP1--><?', $content);
    $content = str_replace('?>', '?><!--CMSinglePHP2-->', $content);


    //make links for all webpages
    $links = '';
    foreach ($webpages as $page) {
        $links .= '<br><a href="#" onclick="goTo(\''.$page.'\')">'.$page.'</a>';
    }

    function getDirContents($path) {
        $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));

        $files = array(); 
        foreach ($rii as $file)
            if (!$file->isDir())
                $files[] = $file->getPathname();

        return $files;
    }

    $images = getDirContents('.');

    $options = '';
    foreach ($images as $image) {
        if (@getimagesize($image)) {
            $options .= '<option value="'.$image.'">'.$image.'</option>';
        }
    }

    $additionalContent = '
        <!--CMSingleBegin-->
        <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.1/css/all.css" integrity="sha384-fnmOCqbTlWIlj8LyTjo7mOUStjsKC4pOpQbqyi7RrhN7udi9RwhKkMHpvLbHG9Sr" crossorigin="anonymous">
        <style type="text/css" scoped>
        .modal {
            display: none; /* Hidden by default */
            position: fixed; /* Stay in place */
            z-index: 99; /* Sit on top */
            left: 0;
            top: 0;
            width: 100%; /* Full width */
            height: 100%; /* Full height */
            overflow: auto; /* Enable scroll if needed */
            background-color: rgb(0,0,0); /* Fallback color */
            background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
        }
        
        /* Modal Content/Box */
        .modal-content {
            background-color: #fefefe;
            margin: 15% auto; /* 15% from the top and centered */
            padding: 20px;
            border: 1px solid #888;
            width: 80%; /* Could be more or less, depending on screen size */
        }
        
        /* The Close Button */
        .close {
            top: 10px;
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }
        
        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
        </style>
            <div id="myModal" class="modal">

                <!-- Modal content -->
                <div class="modal-content">
                <span class="close">&times;</span>
                <h2>change image to...</h2>
                <select id="imgselect" onchange="setPreview()">'.$options.'</select>
                <br>
                <img id="previewImg" style="width: inherit; margin: 20px auto;" src=""/>
                <input id="setI" type="submit" value="change" onclick="setImg()">
                <input id="removeI" type="submit" value="remove" onclick="removeImg()">
                </div>
            
            </div>
            <div id="editBar" style="position:fixed; bottom:0; right:0; z-index: 9; background-color: #f7faff; box-shadow: inset -4px -5px 34px -6px rgba(0,0,0,0.67); max-height: 1000px; transition: max-height 1s;">
                <div id="toggle" style="width:100%; padding:10px; background-color: #c4c7cc;text-align: center;" onclick="toggleV()"><i id="toggleBtn" class="fas fa-chevron-down"></i></div>
                <div style="margin:10px;">
                    <h3>Editor</h3>
                    <button  onclick="document.execCommand(\'bold\',false,null);"><i class="fas fa-bold"></i></button>
                    <button  onclick="document.execCommand(\'italic\',false,null);"><i class="fas fa-italic"></i></button>
                    <button  onclick="document.execCommand(\'underline\',false,null);"><i class="fas fa-underline"></i></button>
                    <br>
                    <button  onclick="document.execCommand(\'decreaseFontSize\',false,null);">smaller</button>
                    <button  onclick="document.execCommand(\'increaseFontSize\',false,null);">bigger</button>
                    <br>
                    <form id="uploadForm" action="admin.php" method="post" enctype="multipart/form-data">
                        <input type="file" name="fileToUpload" id="fileToUpload" onchange="submitUpload()">
                    </form>
                    <button  onclick="callRemove()">Remove Image</button>
                    <input style="width:100%;" type="button" id="saveBtn" value="Save" onclick="saveFile()"/>
                    <br>
                    '.$links.'
                    <br>
                </div>
            </div>

            <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js"></script>
            <script>
                var curPage = "'.$currentFile.'"
                function saveFile() {

                    const url = "admin.php"
                    const formData = new FormData()
                    formData.append("file", curPage)
                    formData.append("content", document.documentElement.innerHTML)
                    formData.append("_token", "'.csrf_token().'") // TODO: insecure

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
                    formData.append("_token", "'.csrf_token().'") // TODO: insecure

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

                $(window).load(function() {
                    $("img").click(function(){
                        imgEditting = this;
                        modal.style.display = "block";
                        document.getElementById("setI").style.display = "block";
                        document.getElementById("removeI").style.display = "none";
                    });
                });

                function addListeners() {
                    $("img").click(function(){
                        imgEditting = this;
                        modal.style.display = "block";
                        document.getElementById("setI").style.display = "block";
                        document.getElementById("removeI").style.display = "none";
                    });
                    modal = document.getElementById("myModal");
                    span = document.getElementsByClassName("close")[0];
                    span.onclick = function() {
                        modal.style.display = "none";
                    }
                }

                function callRemove() {
                    modal.style.display = "block";
                    document.getElementById("setI").style.display = "none";
                    document.getElementById("removeI").style.display = "block";
                }
                
                function setImg() {
                    let e = document.getElementById("imgselect");
                    let value = e.options[e.selectedIndex].value;

                    imgEditting.src = value;
                    modal.style.display = "none";
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
    if(!preg_match('/(<body.*>)/Us', $content)) {
        
        $content = preg_replace('/(^.*\/[^>]*>)/s', '$1 <!--BODYHERE-->', $content);
        $content = str_replace('<!--BODYHERE-->', $additionalContent, $content);
        //$content = '<html>'.$content.'</html>';
        
    } else {
        $content = preg_replace('/(<body.*>)/Us', '$1'.$additionalContent, $content);
    }
    //$admCont = file_get_contents('admin.php');
    //$pos = preg_match('/(^.*)<!-- Content -->/s',$admCont, $matches);
    //file_put_contents('admin.php', $matches[0]);
    file_put_contents(ADMIN_CONTENT, $content);
}


function fillPhp($file, $content) {
    $fileCont = file_get_contents($file);
    preg_match_all('/<\?.*\?>/Us', $fileCont, $matches1);

    var_dump(count($matches1[0]));

    for ($i=0; $i<count($matches1[0]); $i++) {
        echo 'i='.$i;
        if ($i === 0 && strpos($content, '<!--CMSinglePHP1-->') > strpos($content, '<!--CMSinglePHP2-->')) {
            echo '1';
            $content = preg_replace('/^.*<!--CMSinglePHP2-->/Us', $matches1[0][$i], $content);
        } else if ($i === count($matches1[0])-1 && !strpos($content, '<!--CMSinglePHP2-->')) {
            echo '2';
            $content = preg_replace('/<!--CMSinglePHP1-->.*$/Us', $matches1[0][$i], $content);
        } else {
            echo '3';
            $content = preg_replace('/<!--CMSinglePHP1-->.*<!--CMSinglePHP2-->/Us', $matches1[0][$i], $content);
        }
    }

    return $content;
}

try {
    if (file_exists(ADMIN_CONTENT)) {
        include ADMIN_CONTENT;
    } else {
        throw new Exception("No adminContent", 1);
    }
}
catch (\Throwable $th) {
    fillContent('');
    die('There is conflicting php in the file <button onclick="location.reload(); ">reload</button>');
}
