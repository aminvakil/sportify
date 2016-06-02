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
            <li class="active">
                <a class="tournaments" href="#">Tournaments</a>
            </li>
            <li>
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
        </ul>
    </nav>
    <!-- /#sidebar-wrapper -->
    <!-- Page Content -->
    <div id="page-content-wrapper" class="page-content-wrapper content">
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
                <li class="active">
                    <a class="tournaments" href="#">Tournaments</a>
                </li>
                <li>
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
              </ul>
            </div><!-- /.navbar-collapse -->
            <h1 class="text-center">Tournaments</h1>
          </div><!-- /.container-fluid -->
        </nav>
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-10 col-lg-offset-1 col-md-12">
                    <h2 class="text-center">Choose Tournaments</h2>
                    <div class="tournaments-header">
                        <div class="row">
                            <div class="col-lg-5 col-md-5">Tournaments</div>
                            <div class="col-lg-2 col-md-2">Starts</div>
                            <div class="col-lg-2 col-md-2">Ends</div>
                            <div class="col-lg-3 col-md-3">Options</div>
                        </div>
                    </div>
                    <div class="tournament">
                        <div class="row">
                            <div class="col-lg-5 col-md-5 tournament-item">
                                <div class="row">
                                    <div class="col-lg-12 mobile-heading">Tournament</div>
                                    <div class="col-lg-12">UEFA Champions League 2015/2016</div>
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-2 col-xs-6 tournament-item">
                                <div class="row">
                                    <div class="col-lg-12 mobile-heading">Starts</div>
                                    <div class="col-lg-12">2015-09-15 05:00:00</div>
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-2 col-xs-6 tournament-item">
                                <div class="row">
                                    <div class="col-lg-12 mobile-heading">Ends</div>
                                    <div class="col-lg-12">2016-05-29 05:00:00</div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-3"><a href="#" class="btn green-btn tournament-btn">Join</a></div>
                        </div>
                    </div>
                    <div class="tournament">
                        <div class="row">
                            <div class="col-lg-5 col-md-5 tournament-item">
                                <div class="row">
                                    <div class="col-lg-12 mobile-heading">Tournament</div>
                                    <div class="col-lg-12">UEFA Champions League 2015/2016</div>
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-2 col-xs-6 tournament-item">
                                <div class="row">
                                    <div class="col-lg-12 mobile-heading">Starts</div>
                                    <div class="col-lg-12">2015-09-15 05:00:00</div>
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-2 col-xs-6 tournament-item">
                                <div class="row">
                                    <div class="col-lg-12 mobile-heading">Ends</div>
                                    <div class="col-lg-12">2016-05-29 05:00:00</div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-3"><a href="#" class="btn yellow-btn tournament-btn">Leave</a></div>
                        </div>
                    </div>
                    <div class="tournament">
                        <div class="row">
                            <div class="col-lg-5 col-md-5 tournament-item">
                                <div class="row">
                                    <div class="col-lg-12 mobile-heading">Tournament</div>
                                    <div class="col-lg-12">UEFA Champions League 2015/2016</div>
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-2 col-xs-6 tournament-item">
                                <div class="row">
                                    <div class="col-lg-12 mobile-heading">Starts</div>
                                    <div class="col-lg-12">2015-09-15 05:00:00</div>
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-2 col-xs-6 tournament-item">
                                <div class="row">
                                    <div class="col-lg-12 mobile-heading">Ends</div>
                                    <div class="col-lg-12">2016-05-29 05:00:00</div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-3"><a href="#" class="btn green-btn tournament-btn">Join</a></div>
                        </div>
                    </div>
                    <div class="tournament">
                        <div class="row">
                            <div class="col-lg-5 col-md-5 tournament-item">
                                <div class="row">
                                    <div class="col-lg-12 mobile-heading">Tournament</div>
                                    <div class="col-lg-12">UEFA Champions League 2015/2016</div>
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-2 col-xs-6 tournament-item">
                                <div class="row">
                                    <div class="col-lg-12 mobile-heading">Starts</div>
                                    <div class="col-lg-12">2015-09-15 05:00:00</div>
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-2 col-xs-6 tournament-item">
                                <div class="row">
                                    <div class="col-lg-12 mobile-heading">Ends</div>
                                    <div class="col-lg-12">2016-05-29 05:00:00</div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-3"><a href="#" class="btn yellow-btn tournament-btn">Leave</a></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-8 col-lg-offset-2 text-center">
                    <button type="button" class="btn btn-default green-btn">Confirm</button>
                </div>
            </div>
        </div>
    </div>
    <div class="results-slider">
        <div class="container-fluid">
            <div class="current-results-slider owl-carousel">
                <div class="slider-item-holder">
                    <div class="slider-item">
                        <div class="logo">
                            <img src="img/barclays-premier-league.png" alt="Barclays Premier League" />
                        </div>
                        <div class="result-info">
                            <div class="title">Barclays Premier League</div>
                            <div class="points">3p. <span class="position-up"></span></div>
                        </div>
                    </div>
                </div>
                <div class="slider-item-holder">
                <div class="slider-item">
                    <div class="logo">
                        <img src="img/la-liga-logo.png" alt="La Liga" />
                    </div>
                    <div class="result-info">
                        <div class="title">La Liga</div>
                        <div class="points">1p. <span class="position-down"></span></div>
                    </div>
                    </div>
                </div>
                <div class="slider-item-holder">
                <div class="slider-item">
                    <div class="logo">
                        <img src="img/UEFA-champions-league-logo.png" alt="UEFA Champions League" />
                    </div>
                    <div class="result-info">
                        <div class="title">UEFA Champions League</div>
                        <div class="points">3p. <span class="position-same"></span></div>
                    </div>
                    </div>
                </div>
                <div class="slider-item-holder">
                <div class="slider-item">
                    <div class="logo">
                        <img src="img/bundesliga.png" alt="bundesliga" />
                    </div>
                    <div class="result-info">
                        <div class="title">Bundesliga</div>
                        <div class="points">3p. <span class="position-up"></span></div>
                    </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
    <!-- /#page-content-wrapper -->
<script src="../lib/jquery/dist/jquery.min.js"></script>
<script src="../lib/bootstrap-sass/assets/javascripts/bootstrap.min.js"></script>
<script src="../lib/owl.carousel/dist/owl.carousel.min.js"></script>
<script src="js/script.js"></script>
</body>
</html>