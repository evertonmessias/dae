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
                echo DAE::header().DAE::error().DAE::footer();;
            }
        }
    }
}

class DAE
{
    public static function connect($sql)
    {
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
            <h1 class="title">DAE</h1>
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
        if ($_SESSION['dae']) {
            header('Location:select');
        } else {
            echo DAE::header();
        ?>
            <form method="POST">
                <section class="form pb-5">
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
                                <br><br>
                                <button type="submit" name="btnlogin" class="btn btn-primary">Submit</button>

                                <?php
                                if (isset($_POST['btnlogin']) &&  isset($_POST['user']) && isset($_POST['pass'])) {

                                    if ($_POST['user'] == USERNAME && $_POST['pass'] == PASSWORD) {
                                        $_SESSION['dae'] = $_POST['user'];
                                        header('Location:select');
                                    } else {
                                        echo "<p class='error-login'>Username or Password <b>Invalid</b> !</p>";
                                    }
                                }
                                ?>

                            </div>
                        </div>
                    </div>
                </section>
            </form>

        <?php
            echo DAE::footer();
        }
    }

    public static function select()
    {
        if ($_SESSION['dae']) {
            echo DAE::header();
            $sql = "SELECT table_name FROM all_tables;";
            $array_tables = array();
            $array_tables = explode(",", strip_tags(str_replace("</td>", ",", str_replace("TABLE_NAME", "", DAE::connect($sql)))));
            array_pop($array_tables);
            $total = count($array_tables);
        ?>
            <form method="post" action="query">
                <section class="form pb-5">
                    <div class="container">
                        <div class="row">
                            <div class="col-lg-1"></div>
                            <div class="select col-lg-6">
                                <div class="card border-0">
                                    <div class="card-body p-0">
                                        <select onchange="showrow(this.value)" name="table" class="selectpicker form-control border-0 mb-1 px-4 py-4 rounded shadow">
                                            <option value="">Select a Table ?&emsp;(<?php echo $total; ?> tables)</option>
                                            <?php foreach ($array_tables as $tab) { ?>
                                                <option value="<?php echo trim($tab) ?>"><?php echo trim($tab) ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="input col-lg-3"><input class="shadow result" type="number" name="rows" placeholder="Rows ?" required></div>
                            <div class="input col-lg-1"><button title="Submit" class="shadow btn btn-primary" type="submit" name="submit"><i class="ri-play-fill"></i></button></div>
                            <div class="col-lg-1"></div>
                        </div>
                </section>
            </form>
            <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/js/bootstrap.bundle.min.js"></script>
            <script src="/assets/select.js"></script>
        <?php
            echo DAE::footer();
        } else {
            echo DAE::error();
        }
    }

    public static function query()
    {

        if ($_SESSION['dae']) {
            echo DAE::header();
        ?>
            <link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.5.2/css/bootstrap.css" rel="stylesheet">
            <button type="button" class="btn btn-primary btn-floating btn-lg" id="btn-back-to-top"><i class="ri-arrow-up-fill"></i></button>

            <?php

            if (isset($_POST['submit']) && $_POST['table'] != "" && $_POST['rows'] != 0) {

                $table = $_POST['table'];
                $rows = $_POST['rows'];

                $sql = "SELECT * FROM $table WHERE ROWNUM <= $rows;";

                preg_match_all('/<tr>(.*?)<\/tr>/s', utf8_encode(DAE::connect($sql)), $content);
                $results_table = $content[0];

                $thead = array_shift($results_table);
                $tbody = "";

                foreach ($results_table as $rt) {
                    if ($rt != $thead) {
                        $tbody .= $rt;
                    }
                }

                $strings_table = "<table id='result' class='table table-striped table-bordered' style='width:100%'><thead>" . $thead . "</thead><tbody>" . $tbody . "</tbody></table>"; ?>

                <div class="query">

                    <h2><?php echo $table; ?></h2>

                    <?php if ($thead != "" && $tbody != "") {
                        echo $strings_table;
                    } else { ?>

                        <br>
                        <h4>No results</h4>

                    <?php } ?>

                    <br><br>
                    <a href="/select" title="Back"><button type="button" class="btn btn-primary">&emsp;<i class="ri-skip-back-fill"></i>&emsp;</button></a>
                    <br><br>

                </div>
                <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
                <script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap4.min.js"></script>
                <script src="assets/scroll.js"></script>
                <script src="assets/table.js"></script>

        <?php
            } else {
                echo DAE::error();
            }
            echo DAE::footer();
        } else {
            echo DAE::error();
        }
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

    public static function showrow()
    {
        $table = $_POST['tab'];
        $sql = "SELECT COUNT(*) FROM $table;";
        preg_match_all('/<tr>(.*?)<\/tr>/s', DAE::connect($sql), $content);
        $result = trim(strip_tags($content[0][1]));
        echo $result;
    }
}