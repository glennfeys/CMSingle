<?php

//edit password to the one you want
$password = "admin";

$currentFile = '';
$content = '';
$webpages = [];

//if file is uploaded
if (isset($_FILES["fileToUpload"])) {
    $target_dir = "CMSimpleImages/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir);
    }
    $target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
    // Check if image file is a actual image or fake image
    $check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);
    if($check !== false) {
        $uploadOk = 1;
    } else {
        echo "File is not an image.";
        $uploadOk = 0;
    }
    // Check if file already exists
    if (file_exists($target_file)) {
        echo "Sorry, file already exists.";
        $uploadOk = 0;
    }
    // Check file size
    if ($_FILES["fileToUpload"]["size"] > 500000) {
        echo "Sorry, your file is too large.";
        $uploadOk = 0;
    }
    // Check if $uploadOk is set to 0 by an error
    if ($uploadOk == 0) {
        echo "Sorry, your file was not uploaded.";
    // if everything is ok, try to upload file
    } else {
        if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
            echo "Your file was succesfully uploaded";
        } else {
            echo " Sorry, there was an error uploading your file.";
        }
    }
    die();
}


//if there is no password given yet, ask the user for the password
if (!isset($_POST["password"]) || (isset($_POST["password"]) && $_POST["password"] !== $password)) {
    $error = '';
    if (isset($_POST["password"]) && $_POST["password"] !== $password) {
        $error = 'Wrong password !<br>';
    }
    echo $error.'
    <form method="post" action="admin.php">
    password: <input type="password" name="password"> <br>
    <input type="submit" value="log in">
    ';
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
$index = fopen($currentFile, "r") or die("Unable to open file!");
$content = fread($index,filesize($currentFile));
fclose($index);

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

$additionalContent = '
    <body>
        <div style="position:fixed; bottom:0; right:0; z-index: 999;">
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
                    data: { file: curPage, content: document.documentElement.innerHTML, password: "'.$password.'" }
                }).done(alert("succesfully saved"));
            }
            function goTo(file) {
                $.ajax({
                    type: "POST",
                    url: "admin.php",
                    data: { goto: file, password: "'.$password.'" },
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

                fetch(url, {
                    method: "POST",
                    body: formData,
                })
                .then(response => response.text())
                .then(response => alert(response))
            }
            
        //END_CMS</script>
';

// add save buton, links and scripts
if(!strpos($content, '<body>')) {
    $content = $additionalContent.$content;
} else {
    $content = str_replace('<body>', $additionalContent, $content);
}

echo $content;