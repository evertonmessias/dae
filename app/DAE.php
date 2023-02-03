<?php

include 'app/config.php';

session_start();

class Routes
{
    public static function route($action)
    {
        if (!$action) {
            return DAE::login();
        } else {
            $controller = new DAE;
            if (method_exists($controller, $action)) {
                return call_user_func_array(array($controller, $action), []);
            } else {
                echo DAE::header() . DAE::error() . DAE::footer();
            }
        }
    }

    public static function url_active()
    {
        return explode("/", $_SERVER['REQUEST_URI']);
    }
}

class DAE
{
    public static function connect($sql)
    {
        if ($_SESSION['dae'] == "CEBI") {

            $connection = ssh2_connect(ssh_host, ssh_port);

            if (ssh2_auth_password($connection, ssh_user, ssh_pass)) {
                $conttStream = ssh2_exec($connection, 'source .bash_profile; echo "' . $sql . '" | sqlplus -M "HTML ON" ' . orcl);

                $errorStream = ssh2_fetch_stream($conttStream, SSH2_STREAM_STDERR);

                stream_set_blocking($errorStream, true);
                stream_set_blocking($conttStream, true);

                $error = stream_get_contents($errorStream);
                if ($error != "") {
                    return "Error: " . $error;
                }

                $contt = stream_get_contents($conttStream);
                if ($contt != "") {
                    preg_match_all('/SQL&gt;(.*)SQL&gt;/s', $contt, $content);
                    return $content[1][0];
                }

                fclose($errorStream);
                fclose($conttStream);
            } else {
                die("<p>Authentication Failed!</p>");
            }
        } elseif ($_SESSION['dae'] == "ASSESSOR") {
            try {
                $conn = new PDO(fdsn, user, pass);
                return $conn->query($sql);
            } catch (PDOException $e) {
                return "ERROR: " . $e->getMessage();
            }
        }
    }

    public static function header()
    {
?>
        <!DOCTYPE html>
        <html lang="pt-br">

        <head>
            <meta charset="UTF-8">
            <title>Projeto DAE</title>
            <link href="https://cdn.jsdelivr.net/npm/remixicon@2.5.0/fonts/remixicon.css" rel="stylesheet">
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet">
            <link href="https://cdn.jsdelivr.net/npm/choices.js@9.0.1/public/assets/styles/choices.min.css" rel="stylesheet">
            <link href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
            <link href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap4.min.css" rel="stylesheet">
            <link href="/assets/style.css" rel="stylesheet">
            <script src="https://code.jquery.com/jquery-3.5.1.js"></script>
        </head>

        <body>
            <div class="wait-submit">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
            </div>
            <div class="box-title">
                <?php
                if (Routes::url_active()[1] == "query") {
                    echo '<a class="btn-back" href="/select" title="Back"><button type="button" class="btn btn-primary">&emsp;<i class="ri-skip-back-fill"></i>&emsp;</button></a>';
                    echo '<div class="dae-export"></div>';
                }
                ?>
                <h1 class="title">DAE</h1>
                <?php
                if (isset($_SESSION['dae'])) {
                    echo '<a class="btn-logout" href="logout" title="Logout"><button type="button" class="btn btn-danger">Logout</button></a>';
                    echo "<h4>Database: " . $_SESSION['dae'] . "</h4>";
                }
                ?>
            </div>
        <?php
    }

    public static function footer()
    {
        ?>
        </body>

        </html>
        <?php
    }

    public static function login()
    {
        if (isset($_SESSION['dae'])) {
            header('Location:select');
        } else {
            echo self::header();
        ?>
            <form method="POST">
                <div class="container">
                    <div class="row">
                        <div class="login col-lg-4">

                            <div class="form-group">
                                <label>Username
                                    <input type="text" name="user" class="form-control" placeholder="Enter your Username" required>
                                </label>
                            </div>
                            <br>
                            <div class="form-group">
                                <label>Password
                                    <input type="password" name="pass" class="form-control" placeholder="Enter your Password" required>
                                </label>
                            </div>
                            <br>

                            <div class="form-check">
                                <label class="form-check-label"><img src="/assets/logo_oracle.png" class="img-fluid"><input class="form-check-input" type="radio" name="database" value="CEBI" checked> CEBI</label>
                            </div>
                            <div class="form-check">
                                <label class="form-check-label"><img src="/assets/logo_firebase.png" class="img-fluid"><input class="form-check-input" type="radio" name="database" value="ASSESSOR"> ASSESSOR</label>
                            </div>

                            <br>
                            <button type="submit" name="btnlogin" class="btn btn-primary">Submit</button>

                            <?php
                            if (isset($_POST['btnlogin']) &&  isset($_POST['user']) && isset($_POST['pass'])) {
                                if ($_POST['user'] == USERNAME && $_POST['pass'] == PASSWORD) {
                                    $_SESSION['dae'] = $_POST['database'];
                                    header('Location:select');
                                } else {
                                    echo "<p class='error-login'>Username or Password <b>Invalid</b> !</p>";
                                }
                            }
                            ?>

                        </div>
                    </div>
                </div>
            </form>

        <?php
            echo self::footer();
        }
    }

    public static function select()
    {
        echo self::header();

        if (isset($_SESSION['dae'])) {
        ?>
            <br>
            <form class="select" method="post" action="query">
                <div class="container">
                    <div class="row">
                        <div class="select col-lg-12">
                            <h5>Write a Query:</h5>
                            <?php
                            if ($_SESSION['dae'] == 'CEBI') {  ?>
                                <small>
                                    e.g.: SELECT DATA,NOME_DETALHE,VALOR,TIPO,EXERCICIO FROM MOVIMENTO_EMPENHOS_RECEITAS WHERE (TIPO LIKE 'RECEITA' OR TIPO LIKE 'PAGAMENTO') AND EXERCICIO LIKE '2022' AND DATA BETWEEN TO_DATE('01-JAN-22','DD-MON-YY') AND TO_DATE('31-DEC-22','DD-MON-YY') ORDER BY 1 DESC;
                                </small>
                            <?php } ?>
                            <textarea class="form-control" name="query" rows="5"></textarea>
                        </div>
                        <br>
                        <div class="input">
                            <button title="Submit" class="shadow btn btn-primary" type="submit" name="submit"><i class="ri-play-fill"></i></button>
                        </div>
                    </div>
            </form>
        <?php

        } else {
            echo self::error();
        }
        echo self::footer();
        ?>
    <?php
    }

    public static function query()
    {
        echo self::header();
    ?>
        <link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.5.2/css/bootstrap.css" rel="stylesheet">
        <link type="text/css" href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap4.min.css" rel="stylesheet" />
        <link type="text/css" href="https://cdn.datatables.net/autofill/2.4.0/css/autoFill.bootstrap4.css" rel="stylesheet" />
        <link type="text/css" href="https://cdn.datatables.net/buttons/2.2.3/css/buttons.bootstrap4.min.css" rel="stylesheet" />

        <button type="button" class="btn btn-primary btn-floating btn-lg" id="btn-back-to-top"><i class="ri-arrow-up-fill"></i></button>
        <?php

        if ($_SESSION['dae'] == "CEBI") {

            if (isset($_POST['submit']) && $_POST['query']) {

                $sql = $_POST['query'];

                preg_match_all('/<tr>(.*?)<\/tr>/s', utf8_encode(self::connect($sql)), $content);
                $results_table = $content[0];
                $thead = array_shift($results_table);
                $tbody = "";
                foreach ($results_table as $rt) {
                    if ($rt != $thead) {
                        $tbody .= $rt;
                    }
                }
                $strings_table = "<div class='container'><div class='row'><div class='col-lg-12'><br><table id='result' class='table table-striped table-bordered' style='width:100%'><thead>" . $thead . "</thead><tbody>" . $tbody . "</tbody></table></div></div></div><br>";

                echo $strings_table;
            } else {
                echo self::error();
            }
        } elseif ($_SESSION['dae'] == "ASSESSOR") {

            if (isset($_POST['submit']) && $_POST['query']) {

                $sql = $_POST['query'];

                $array = self::connect($sql)->fetchAll(PDO::FETCH_ASSOC);

                $thead = "<tr>";
                foreach ($array[0] as $key => $value) {
                    $thead .= "<th>" . $key . "</th>";
                }
                $thead .= "</tr>";

                $tbody = "";
                foreach ($array as $row) {
                    $tbody .= "<tr>";
                    foreach ($row as $key => $value) {
                        $tbody .= "<td>" . $value . "</td>";
                    }
                    $tbody .= "</th>";
                }

                $strings_table = "<div class='container'><div class='row'><div class='col-lg-12'><br><table id='result' class='table table-striped table-bordered' style='width:100%'><thead>" . $thead . "</thead><tbody>" . utf8_encode($tbody) . "</tbody></table></div></div></div><br>";

                echo $strings_table;
            } else {
                echo self::error();
            }
        } else {
            echo self::error();
        }
        echo self::footer();
        ?>
        <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap4.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/2.5.0/jszip.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js"></script>
        <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap4.min.js"></script>
        <script src="https://cdn.datatables.net/autofill/2.4.0/js/dataTables.autoFill.min.js"></script>
        <script src="https://cdn.datatables.net/autofill/2.4.0/js/autoFill.bootstrap4.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.2.3/js/dataTables.buttons.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.bootstrap4.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.html5.min.js"></script>
        <script src="assets/script.js"></script>
    <?php
    }

    public static function error()
    {
    ?>
        <br>
        <h2>Error 403 Forbidden<br><br>
            <a href='/' title='Go Back'><button type='button' class='btn btn-primary'>&emsp;<i class='ri-skip-back-fill'></i>&emsp;</button></a>
        </h2>
<?php
    }

    public static function logout()
    {
        unset($_SESSION['dae']);
        session_destroy();
        header('Location:/');
    }

    public static function apicebi()
    {
        ?>
        <style>
            .apicebi{
                border-spacing: 0;
                width: 100%;
            }
            th,td{
                border: 1px solid #000;
                padding: 3px;
            }
        </style>
        <?php
        $_SESSION['dae'] = "CEBI";

        $sql = "SELECT DATA,NOME_DETALHE,VALOR,TIPO,EXERCICIO FROM MOVIMENTO_EMPENHOS_RECEITAS WHERE (TIPO LIKE 'RECEITA' OR TIPO LIKE 'PAGAMENTO') AND EXERCICIO LIKE '2022' AND DATA BETWEEN TO_DATE('01-JAN-22','DD-MON-YY') AND TO_DATE('31-DEC-22','DD-MON-YY') ORDER BY 1 DESC;";

        preg_match_all('/<tr>(.*?)<\/tr>/s', utf8_encode(DAE::connect($sql)), $content);

        $results_table = $content[0];
        $thead = array_shift($results_table);
        $tbody = "";
        foreach ($results_table as $rt) {
            if ($rt != $thead) {
                $tbody .= $rt;
            }
        }
        $table = "<table class='apicebi'>" . $thead . $tbody . "</table>";
        $pattern = '/\n| scope\=\"col\"| align\=\"right\"/i';;
        $output = preg_replace($pattern, "", $table);
        echo $output;
    }
}
