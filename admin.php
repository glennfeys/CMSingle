<?php

//edit password to the one you want
$password = "admin";

$currentFile = '';
$content = '';
$webpages = [];

//if there is no password given yet, ask the user for the password
if (!isset($_POST["password"]) || (isset($_POST["password"]) && $_POST["password"] !== $password)) { // TODO: should change to hash + session/token auth
    $error = '';
    if (isset($_POST["password"]) && $_POST["password"] !== $password) {
        $error = 'Wrong password !<br>';
    }
    fillContent('', $password);
    die($error.'
    <form method="post" action="admin.php">
    password: <input type="password" name="password"> <br>
    <input type="submit" value="log in">
    ');
}


// If file is uploaded
if (isset($_FILES["fileToUpload"])) {
    $target_dir = "CMSimpleImages/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir);
    }

    if($_FILES["fileToUpload"]["error"] !== UPLOAD_ERR_OK) {
        echo "Invalid upload";
    } else {
        $target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
        $imageFileType = pathinfo($target_file, PATHINFO_EXTENSION);

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

//this is executed when 'Save' is pressed and will set the value to the editted value
if (isset($_POST["file"]) && isset($_POST["content"])) {
    $file = $_POST["file"];
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
// this should strictly be a get request but because we also send the password as data we use post instead
if (isset($_POST["goto"])) {
    $currentFile = $_POST["goto"];
    fillContent($currentFile, $password);
    die('reload');
}

function fillContent($currentFile='', $password) {

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
                <input type="submit" value="change" onclick="setImg()">

                </div>
            
            </div>
            <div id="editBar" style="position:fixed; bottom:0; right:0; z-index: 9; padding: 10px;background-color: #f7faff; box-shadow: inset -4px -5px 34px -6px rgba(0,0,0,0.67);">
                <div id="toggle" style="width:100%; height:25px; background-color: #c4c7cc;text-align: center;" onclick="toggleV()">Toggle visibility</div>
                <h3>Editor</h3>
                <button  onclick="document.execCommand(\'bold\',false,null);">Bold</button>
                <button  onclick="document.execCommand(\'italic\',false,null);">Italic</button>
                <button  onclick="document.execCommand(\'underline\',false,null);">underline</button>
                <br>
                <button  onclick="document.execCommand(\'decreaseFontSize\',false,null);">smaller</button>
                <button  onclick="document.execCommand(\'increaseFontSize\',false,null);">bigger</button>
                <br>
                <form id="uploadForm" action="admin.php" method="post" enctype="multipart/form-data">
                    <input type="file" name="fileToUpload" id="fileToUpload" onchange="submitUpload()">
                </form>
                <input style="width:100%;" type="button" id="saveBtn" value="Save" onclick="saveFile()"/>
                <br>
                '.$links.'
                <br>
                
            </div>

            <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js"></script>
            <script>
                var curPage = "'.$currentFile.'"
                function saveFile() {

                    const url = "admin.php"
                    const formData = new FormData()
                    formData.append("file", curPage)
                    formData.append("content", document.documentElement.innerHTML)
                    formData.append("password", "' . $password . '") // TODO: insecure

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
                    formData.append("password", "' . $password . '") // TODO: insecure

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
                        document.getElementById("editBar").style.height = "40px";
                    } else {
                        document.getElementById("editBar").style.height = "auto";
                    }
                    toggled = !toggled;
                }
                
                function submitUpload() {
                    const url = "admin.php"
                    const form = document.querySelector("#uploadForm")
                    const formData = new FormData()
                    let image = document.getElementById("fileToUpload").files[0];
                    formData.append("fileToUpload", image)
                    formData.append("password", "' . $password . '") // TODO: insecure

                    fetch(url, {
                        method: "POST",
                        body: formData,
                    })
                    .then(response => response.text())
                    .then(response => alert(response))
                }

                var imgEditting;

                $(window).load(function() {
                    $("img").click(function(){
                        imgEditting = this;
                        modal.style.display = "block";
                    });
                });

                function addListeners() {
                    $("img").click(function(){
                        imgEditting = this;
                        modal.style.display = "block";
                    });
                    modal = document.getElementById("myModal");
                    span = document.getElementsByClassName("close")[0];
                    span.onclick = function() {
                        modal.style.display = "none";
                    }
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
    file_put_contents('adminContent.php', $content);
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
    include "adminContent.php";
} 
catch (\Throwable $th) {
    fillContent('', $password);
    die('There is conflicting php in the file <button onclick="location.reload(); ">reload</button>');
}
