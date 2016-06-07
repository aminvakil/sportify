<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <title>Sportify</title>
    <link href='https://fonts.googleapis.com/css?family=Exo:400,600,700,900' rel='stylesheet' type='text/css'>
    <link rel="stylesheet" type="text/css" href="css/style.css">

</head>
<body>
 <div id="wrapper" class="wrapper toggled">
    <!-- Sidebar -->
    <nav class="navbar navbar-inverse navbar-fixed-top sidebar-wrapper" id="sidebar-wrapper" role="navigation">
        <ul class="nav sidebar-nav">
            <li class="sidebar-brand">
                <button class="hamburger is-open" data-toggle="offcanvas" ><?php include('img/menu.svg'); ?></button>
            </li>
            <li class="my-profile">
                <a class="profile-picture" href="#">
                    <img src="img/profile_pic.jpg" alt="" />
                </a>
                <p class="user-name">Botzi Dimitrova</p>
            </li>
            <li>
                <a class="tournaments" href="#">Tournaments</a>
            </li>
            <li class="active">
                <a class="matches" href="#">Matches</a>
            </li>
            <li>
                <a class="standings" href="#">Standings</a>
            </li>
            <li>
                <a class="history" href="#">History</a>
            </li>
            <li>
                <a class="rules" href="#">Rules</a>
            </li>
            <li>
                <a class="login" href="#">Log in</a>
            </li>
            <li>
                <a class="signup" href="#">Sign up</a>
            </li>
        </ul>
    </nav>
    <!-- /#sidebar-wrapper -->
    <!-- Page Content -->
    <div id="page-content-wrapper" class="page-content-wrapper">
        <nav id="navbar" class="navbar navbar-default navbar-fixed-top">
          <div class="container-fluid">
            <!-- Brand and toggle get grouped for better mobile display -->
            <div class="navbar-header">
              <button type="button" class="pull-left navbar-toggle collapsed" data-toggle="collapse" data-target="#mobile-menu" aria-expanded="false">
                <span class="sr-only">Toggle navigation</span>
                <?php include('img/menu.svg'); ?>
              </button>
            </div>

            <!-- Collect the nav links, forms, and other content for toggling -->
            <div class="collapse navbar-collapse mobile-menu" id="mobile-menu">
              <ul class="nav navbar-nav">
                <li class="my-profile">
                    <a href="#">My Profile</a>
                </li>
                <li>
                    <a class="tournaments" href="#">Tournaments</a>
                </li>
                <li class="active">
                    <a class="matches" href="#">Matches</a>
                </li>
                <li>
                    <a class="standings" href="#">Standings</a>
                </li>
                <li>
                    <a class="history" href="#">History</a>
                </li>
                <li>
                    <a class="rules" href="#">Rules</a>
                </li>
                <li>
                    <a class="login" href="#">Log in</a>
                </li>
                <li>
                    <a class="signup" href="#">Sign up</a>
                </li>
              </ul>
            </div><!-- /.navbar-collapse -->
            <h1 class="text-center">Sportify</h1>
          </div><!-- /.container-fluid -->
        </nav>
        <div class="container content login-screen">
            <div class="row">
                <div class="col-sm-6 col-sm-offset-3 text-center">
                    <h2>Log in to place your bets</h2>
                    <form class="login-form">
                      <div class="form-group">
                        <input name="email" type="email" class="form-control" placeholder="Email address">
                      </div>
                      <div class="form-group">
                        <input name="password" type="password" class="form-control" placeholder="Password">
                      </div>
                      <p class="forgotten-password"><a href="#">Forgotten password?</a></p>
                      <button type="submit" class="btn btn-default green-btn">Login</button>
                    </form>
                    <h2>Don't have an account?<br/>
                    <a href="signup.php">Sign up here</a>
                    </h2>
                </div>
            </div>
        </div>
    </div>
</div>
    <!-- /#page-content-wrapper -->
<script src="js/all-scripts.js"></script>
</body>
</html>