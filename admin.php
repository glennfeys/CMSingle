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

    //remove the editable content tags and restore the php tags
    $content = str_replace('contenteditable="true"', '', $content);
    $content = str_replace('<!--?php', '<?php', $content);
    $content = str_replace('?-->', '?>', $content);

    //remove the inserted js
    $pattern = '/<body>.*END_CMS<\/script>/s';
    $replacement = '<body>';
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
}

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
    <body>
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
            <img id="previewImg" style="width: inherit;" src=""/>
            <input type="submit" value="change" onclick="setImg()"></input>

            </div>
        
        </div>
        <div style="position:fixed; bottom:0; right:0; z-index: 9; padding: 10px; border: 1px solid #000000;">
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
                $.ajax({
                    type: "POST",
                    url: "admin.php",
                    data: { file: curPage, content: document.documentElement.innerHTML, password: "'.$password.'" } // TODO: insecure
                }).done(alert("succesfully saved"));
            }
            function goTo(file) {
                $.ajax({
                    type: "POST",
                    url: "admin.php",
                    data: { goto: file, password: "'.$password.'" }, // TODO: insecure
                    success: function(response){
                        document.body.parentElement.innerHTML = response;
                        curPage = file
                    }
                });
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
            
        //END_CMS</script>
';

// add save buton, links and scripts
if(!strpos($content, '<body>')) {
    $content = $additionalContent.$content;
} else {
    //TODO do this with regex to find body
    $content = str_replace('<body>', $additionalContent, $content);
}

echo $content;
