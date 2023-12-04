
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
        <title>Comming Soon Page</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.6.0/css/bootstrap.min.css">
        <link rel="stylesheet" href="assets/css/styles.css">
    </head>
    <body >
        <style>
            .overlay {
                height: 100%;
                width: 0;
                position: fixed;
                z-index: 1;
                top: 0;
                left: 0;
                background: rgb(2,0,36);
                background: linear-gradient(4deg, rgba(2,0,36,1) 0%, rgba(102,11,144,1) 100%);
                overflow-x: hidden;
                transition: 0.5s;
            }
            .center-ld-content{
                background-color: black;
                color:white;
                padding: 5%;
            }
            .maintenance-login{
                padding:15px;
                background:black;
                border-radius:5px;
                margin-left: -60px;
            }
            .maintenance-login:hover{
                margin-left: 0px;
                animation-name: loginspan;
                animation-duration: 1s;
            }
            @keyframes loginspan {
                from {margin-left: -60px;}
                to {margin-left: 0px;}
            }
            
        </style>

        <?php 

        $dbcern_maintenance_options = get_option('dbcern_maintenance_mode',true);
        // var_dump($dbcern_maintenance_options);

        ?>

        <div id="myNav" class="overlay" style="width:100%;">
            <div class="container-fluid text-center" style="padding: 0px;padding-top: 0px;padding-right: 0px;padding-bottom: 0px;padding-left: 0px;">
                <div class="row text-center justify-content-center align-items-center" style="height: 90vh;width: 100%;margin: 0px;">
                    <div class="col text-center center-ld-content" >
                        <h1>Coming Soon</h1>
                        <h4>Our website is under construction.<br></h4>
                        <h4><?php echo home_url(); ?></h4>
                        

                    </div>
                </div>
            </div>
            <span class="maintenance-login"><a href="<?php echo home_url(); ?>/wp-admin">LogIn</a></span>
        </div>
        
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.6.0/js/bootstrap.bundle.min.js"></script>
        
    </body>
</html>

<?php // get_footer(); ?>
