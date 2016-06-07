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
            <li>
                <a class="matches" href="#">Matches</a>
            </li>
            <li>
                <a class="standings" href="#">Standings</a>
            </li>
            <li class="active">
                <a class="history" href="#">History</a>
            </li>
            <li>
                <a class="rules" href="#">Rules</a>
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
                <li>
                    <a class="matches" href="#">Matches</a>
                </li>
                <li>
                    <a class="standings" href="#">Standings</a>
                </li>
                <li class="active">
                    <a class="history" href="#">History</a>
                </li>
                <li>
                    <a class="rules" href="#">Rules</a>
                </li>
              </ul>
            </div><!-- /.navbar-collapse -->
            <h1 class="text-center">History</h1>
          </div><!-- /.container-fluid -->
        </nav>
        <div class="container-fluid content">
            <div class="matches-filters row">
                <div class="col-lg-10 col-lg-offset-1">
                    <form id="history-form" method="GET">
                        <div class="row">
                            <div class="col-sm-5">
                            <label>Tournament</label>
                                <select name="tournament_id" id="username-list" class="form-control">
                                    <option value="ALL">All joined</option>
                                </select>
                            </div>
                            <div class="col-sm-3">
                                <label>Username</label>
                                <select name="username" id="tournament-id" class="form-control">
                                    <option value="ALL">All joined</option>
                                </select>
                            </div>
                            <div class="col-sm-2">
                                <label>Date From</label>
                                <input name="date_from" id="date-from" class="form-control" type="date" />
                            </div>
                            <div class="col-sm-2">
                                <label>Date To</label>
                                <input name="date_to" id="date-to" class="form-control" type="date" />
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-8 col-lg-offset-2 col-md-12">
                    <div class="match">
                        <div class="row">
                            <div class="match-date text-center">22.03.2016</div>
                            <div class="match-title text-center">UEFA EURO 2016</div>
                            <div class="col-lg-12">
                                <div class="row">
                                    <div class="col-xs-6 text-left">
                                        <div class="team-title">Home team</div>
                                    </div>
                                    <div class="col-xs-6 text-right">
                                        <div class="team-title">Away team</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-12">
                                <div class="match-item">
                                    <div class="row">
                                        <div class="col-sm-4 match-item-mobile">Bulgaria</div>
                                        <div class="col-sm-4 bettings">
                                            <div class="bet-rectangle">
                                                <?php include('img/result-rectangle.svg'); ?>
                                            </div>
                                            <div class="bet-results">
                                                <div class="bet-field">4</div>
                                                <span>:</span>
                                                <div class="bet-field">4</div>
                                            </div>
                                        </div>
                                        <div class="col-sm-4 match-item-mobile">Romania</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-12">
                                <div class="row">
                                    <div class="col-xs-6 text-left">
                                        <div class="prediction-title">Predictions: <span class="predictions">4:4</span></div>
                                    </div>
                                    <div class="col-xs-6 text-right">
                                        <div class="points-title">Points gained: <span class="points-gained">3pt</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="match">
                        <div class="row">
                            <div class="match-date text-center">22.03.2016</div>
                            <div class="match-title text-center">UEFA EURO 2016</div>
                            <div class="col-lg-12">
                                <div class="row">
                                    <div class="col-xs-6 text-left">
                                        <div class="team-title">Home team</div>
                                    </div>
                                    <div class="col-xs-6 text-right">
                                        <div class="team-title">Away team</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-12">
                                <div class="match-item">
                                    <div class="row">
                                        <div class="col-sm-4 match-item-mobile">Bulgaria</div>
                                        <div class="col-sm-4 bettings">
                                            <div class="bet-rectangle">
                                                <?php include('img/result-rectangle.svg'); ?>
                                            </div>
                                            <div class="bet-results">
                                                <div class="bet-field">4</div>
                                                <span>:</span>
                                                <div class="bet-field">4</div>
                                            </div>
                                        </div>
                                        <div class="col-sm-4 match-item-mobile">Romania</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-12">
                                <div class="row">
                                    <div class="col-xs-6 text-left">
                                        <div class="prediction-title">Predictions: <span class="predictions">4:4</span></div>
                                    </div>
                                    <div class="col-xs-6 text-right">
                                        <div class="points-title">Points gained: <span class="points-gained">3pt</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="match">
                        <div class="row">
                            <div class="match-date text-center">22.03.2016</div>
                            <div class="match-title text-center">UEFA EURO 2016</div>
                            <div class="col-lg-12">
                                <div class="row">
                                    <div class="col-xs-6 text-left">
                                        <div class="team-title">Home team</div>
                                    </div>
                                    <div class="col-xs-6 text-right">
                                        <div class="team-title">Away team</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-12">
                                <div class="match-item">
                                    <div class="row">
                                        <div class="col-sm-4 match-item-mobile">Bulgaria</div>
                                        <div class="col-sm-4 bettings">
                                            <div class="bet-rectangle">
                                                <?php include('img/result-rectangle.svg'); ?>
                                            </div>
                                            <div class="bet-results">
                                                <div class="bet-field">4</div>
                                                <span>:</span>
                                                <div class="bet-field">4</div>
                                            </div>
                                        </div>
                                        <div class="col-sm-4 match-item-mobile">Romania</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-12">
                                <div class="row">
                                    <div class="col-xs-6 text-left">
                                        <div class="prediction-title">Predictions: <span class="predictions">4:4</span></div>
                                    </div>
                                    <div class="col-xs-6 text-right">
                                        <div class="points-title">Points gained: <span class="points-gained">3pt</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="results-slider" class="results-slider">
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
<script src="js/all-scripts.js"></script>
</body>
</html>