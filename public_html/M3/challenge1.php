<!--
Challenge 1: Full-Page Layout with Scrollable Content
-----------------------------------------------------
Requirements:
 #1 The header and footer should remain FIXED in place (top and bottom of page respectively)
 #2 The content area should SCROLL independently (nothing should be pushed off screen and the browser WINDOW scrollbar shouldn't appear)
 #3 The entire page should always take up the full viewport height
 #4 The borders around header,main,footer should remain intact and visible, this will help show that the challenges were solved correctly
-->

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Full-Page Layout </title>
    <link rel="stylesheet" href="styles.css">
</head>

<body class="challenge1">
    <nav>
        <ul>
            <li><a href="challenge1.php" class="active">Challenge 1</a></li>
            <li><a href="challenge2.php">Challenge 2</a></li>
            <li><a href="challenge3.php">Challenge 3</a></li>
        </ul>
    </nav>
    <!-- Edit your UCID here -->
    <header>Header Section (dns33)</header>
    <!-- Don't make any other edits to the HTML -->
    <main>
        <div class="content">
            <h2>Lorem Ipsum</h2>
            <section>
                <ul>
                    <li>The header and footer should remain <strong>fixed</strong> in place (top and bottom of page respectively).</li>
                    <li>The content area should <strong>scroll independently</strong> (nothing should be pushed off screen and the browser window scrollbar shouldn't appear).</li>
                    <li>The entire page should always take up the <strong>full viewport height</strong>.</li>
                    <li>The borders around header, main, and footer should remain <strong>intact and visible</strong>; this will help show that the challenges were solved correctly.</li>
                </ul>
            </section>
            <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec id posuere felis. Pellentesque tincidunt,
                mauris sed pretium vehicula, enim odio lacinia urna, vel blandit sapien turpis at enim. Suspendisse a
                tortor vel nunc lobortis dapibus in id dolor. Morbi elementum ligula nec dictum fringilla. Sed sed
                sagittis diam, et consequat lacus. Suspendisse non dui mauris. Vivamus molestie mattis libero, nec
                placerat dolor imperdiet vitae. Etiam dapibus orci lacus, eu tristique ipsum sagittis in. Fusce
                porttitor mi non consectetur dictum.</p>
        </div>
    </main>

    <footer>Footer Section</footer>
</body>

</html>
<script src="util.js"></script>

<style>
    /* You're free to make whatever edits you need here, just don't override anything in the styles.css */
    /* See the requirements at the top. If easier, you may copy/paste them here*/
    html, body{
        height: 100%;
        margin: 0;
    }

    body.challenge1 {
        height:100vh;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    body.challenge1 nav {
        flex: 0 0 auto;
    }
    body.challenge1 header,
    body.challenge1 footer {
        flex: 0 0 auto;
    }

    body.challenge1 main {
        flex: 1 1 auto;
        overflow-y: auto;      
         min-height: 0;         
    }
    

</style>