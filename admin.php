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
    var_dump($_POST);
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

if (isset($_POST["fileDelete"]) && is_file($_POST["fileDelete"])) {
    unlink($_POST["fileDelete"]);
    die("succesfully deleted file");
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
        width: 50%; /* Could be more or less, depending on screen size */
        height: 600px;
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
            <img id="previewImg" style="width: auto; height: 320px; margin: 20px auto;" src=""/>
            <br>
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
                        addListeners()
                    }
                });
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
                formData.append("password", "' . $password . '") // TODO: insecure

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
                        option.text = ".\\\\CMSimpleImages\\\\"+image.name;
                        option.value = ".\\\\CMSimpleImages\\\\"+image.name;
                        x.add(option);
                    }
                });
            }

            function removeImg() {
                let e = document.getElementById("imgselect");
                let value = e.options[e.selectedIndex].value;
                const formData = new FormData();
                formData.append("password", "' . $password . '");
                formData.append("fileDelete", value);

                fetch("admin.php", {
                    method: "POST",
                    body: formData,
                })
                .then(response => response.text())
                .then(response => {
                    alert(response);
                    if (response === "succesfully deleted file") {
                        e.remove(e.selectedIndex);
                    }
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