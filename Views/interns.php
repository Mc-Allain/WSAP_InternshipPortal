<?php
    session_start();

    require_once "../Controllers/Functions.php";

    if (!isset($_SESSION["intern_id"]) || !isset($_SESSION["password"])) {
        redirect("../index.php");
        exit();
    }
    
    require_once "../Controllers/Database.php";
    require_once "../Controllers/Date.php";

    $db = new Database();
    $date = new Date();
    
    $db->query("SELECT intern_personal_information.*, intern_roles.*, roles.*
    FROM intern_personal_information, intern_roles, roles
    WHERE intern_personal_information.id=intern_roles.intern_id AND
    intern_roles.role_id=roles.id AND roles.admin=1 AND
    intern_personal_information.id=:intern_id");
    $db->setInternId($_SESSION["intern_id"]);
    $db->execute();
    $admin_info = $db->fetch();
    $admin_roles_count = $db->rowCount();
    
    if (isset($_POST["addIntern"])) {
        $last_name = toProper(fullTrim($_POST["lastName"]));
        $first_name = toProper(fullTrim($_POST["firstName"]));
        $middle_name = toProper(fullTrim($_POST["middleName"]));
        $gender = $_POST["gender"];
        $department_id = $_POST["department"];

        if (!empty($last_name) && !empty($first_name)) {
            $intern_id = $date->getYear()."-".randomWord();

            $personal_info = array($intern_id, $last_name, $first_name, $middle_name, $gender);
    
            $db->query("INSERT INTO intern_personal_information (id, last_name, first_name, middle_name, gender)
            VALUES(:intern_id, :last_name, :first_name, :middle_name, :gender)");
            $db->insertPersonalInfo($personal_info);
            $db->execute();
            $db->closeStmt();

            $db->query("INSERT INTO intern_wsap_information (id, department_id) VALUES(:intern_id, :department_id)");
            $db->setInternId($intern_id);
            $db->setDeptId($department_id);
            $db->execute();
            $db->closeStmt();
                    
            $log_value = $admin_info["last_name"].", ".$admin_info["first_name"].
                " (".$admin_info["name"].") added an account for ".$last_name.", ".$first_name." (".$intern_id.").";
    
            $log = array($date->getDateTime(),
            strtoupper($_SESSION["intern_id"]),
            $log_value);
    
            $db->query("INSERT INTO audit_logs
            VALUES (NULL, :timestamp, :intern_id, :log)");
            $db->log($log);
            $db->execute();
            $db->closeStmt();
            
            $_SESSION["success"] = "Successfully added an account.";
        } else {
            $_SESSION["failed"] = "Please fill-out the required fields!";
        }
        redirect("interns.php");
        exit();
    }
    
    if (isset($_POST["removeAccount"])) {
        if (!empty($_POST["intern_id"]) && !empty($_POST["fullName"])) {    
            $db->query("DELETE FROM intern_personal_information WHERE id=:intern_id");
            $db->setInternId($_POST["intern_id"]);
            $db->execute();
            $db->closeStmt();
            
            $db->query("DELETE FROM intern_wsap_information WHERE id=:intern_id");
            $db->setInternId($_POST["intern_id"]);
            $db->execute();
            $db->closeStmt();
                    
            $log_value = $admin_info["last_name"].", ".$admin_info["first_name"].
                " (".$admin_info["name"].") removed the account of ".$_POST["fullName"].".";
    
            $log = array($date->getDateTime(),
            strtoupper($_SESSION["intern_id"]),
            $log_value);
    
            $db->query("INSERT INTO audit_logs
            VALUES (NULL, :timestamp, :intern_id, :log)");
            $db->log($log);
            $db->execute();
            $db->closeStmt();
            
            $_SESSION["success"] = "Successfully removed an account.";
        } else {
            $_SESSION["failed"] = "Please fill-out the required fields!";
        }
        redirect("interns.php");
        exit();
    }

    if (isset($_POST["search"])) {
        $parameters = "?";
        if (!empty($_POST["search_intern"])) {
            $parameters = $parameters."search=".$_POST["search_intern"];
        }

        if (!empty($_GET["department"])) {
            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
            $parameters = $parameters."department=".$_GET["department"];
        }

        if (isset($_GET["status"])) {
            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
            $parameters = $parameters."status=".$_GET["status"];
        }
        
        if (!empty($_GET["sort"])) {
            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
            $parameters = $parameters."sort=".$_GET["sort"];
        }

        if (strlen($parameters) > 1) {
            redirect("interns.php".$parameters);
        } else {
            redirect("interns.php");
        }

        exit();
    }

    if (isset($_POST["reset"])) {
        redirect("interns.php");
        exit();
    }

    require_once "../Templates/header_view.php";
    setTitle("Interns");
?>
<div class="my-container"> 
    <?php
        include_once "nav_side_bar.php";
        navSideBar("interns");
    ?>
    <div class="main-section p-4">
        <div class="aside">
            <?php include_once "profile_nav.php"; ?>
        </div>
        
        <div class="d-flex align-items-center mb-2">
            <div>
                <h3>Interns</h3>
            </div>
        </div>        

        <div class="mb-2">
            <div class="row">
                <!--SEARCH BUTTON/TEXT-->
                <form method="post">
                    <div class="col-lg-8 col-md-10 col-sm-12">
                        <div class="input-group mb-3">
                            <input type="text" class="form-control" placeholder="Search Intern" name="search_intern" value="<?php
                            if (!empty($_GET["search"])) {
                                echo $_GET["search"];
                            } ?>">
                            <div class="input-group-append">
                                <button class="btn btn-indigo" type="submit" name="search">Search</button>
                                <button class="btn btn-danger" name="reset">Reset</button>
                            </div>
                        </div>
                    </div>
                <form>
                
                <div class="col-12">
                    <div class="w-100 d-md-flex">
                        <div class="d-flex mb-2">
                            <!--DEPARTMENT DROPDOWN-->
                            <div class="dropdown align-center me-2">
                                <button class="btn btn-light border-dark dropdown-toggle" type="button" id="dropdownMenuButton1"
                                data-bs-toggle="dropdown" aria-expanded="false" name="department"> <?php
                                    if (empty($_GET["department"])) {
                                        echo "All Departments";
                                    } else {
                                        echo $_GET["department"];
                                    } ?>
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                                    <li><a class="dropdown-item btn-smoke" <?php
                                    $parameters = "?";
                                    if (!empty($_GET["search"])) {
                                        $parameters = $parameters."search=".$_GET["search"];
                                    }

                                    if (isset($_GET["status"])) {
                                        if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                        $parameters = $parameters."status=".$_GET["status"];
                                    }
                                    
                                    if (!empty($_GET["sort"])) {
                                        if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                        $parameters = $parameters."sort=".$_GET["sort"];
                                    }

                                    if (strlen($parameters) > 1) { ?>
                                        href="<?= "interns.php".$parameters ?>" <?php
                                    } else { ?>
                                        href="<?= "interns.php" ?>" <?php
                                    } ?>> All Departments </a></li> <?php
                                    
                                    $db->query("SELECT * FROM departments ORDER BY name");
                                    $db->execute();
                                    
                                    while ($row = $db->fetch()) { ?>
                                        <li><a class="dropdown-item btn-smoke" <?php
                                        $parameters = "?";
                                        if (!empty($_GET["search"])) {
                                            $parameters = $parameters."search=".$_GET["search"];
                                        }

                                        if (!empty($row["name"])) {
                                            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                            $parameters = $parameters."department=".$row["name"];
                                        }

                                        if (isset($_GET["status"])) {
                                            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                            $parameters = $parameters."status=".$_GET["status"];
                                        }
                                        
                                        if (!empty($_GET["sort"])) {
                                            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                            $parameters = $parameters."sort=".$_GET["sort"];
                                        }

                                        if (strlen($parameters) > 1) { ?>
                                            href="<?= "interns.php".$parameters ?>" <?php
                                        } else { ?>
                                            href="<?= "interns.php" ?>" <?php
                                        } ?>> <?= $row["name"] ?>
                                        </a></li> <?php
                                    } ?>
                                </ul>
                            </div>
                            <!--STATUS DROPDOWN-->
                            <div class="dropdown me-2">
                                <button class="btn btn-light border-dark dropdown-toggle" type="button" id="dropdownMenuButton1"
                                data-bs-toggle="dropdown" aria-expanded="false"> <?php
                                    if (isset($_GET["status"])) {
                                        switch ($_GET["status"]) {
                                            case "0":
                                                echo "Inactive";
                                                break;
                                            case "1":
                                                echo "Active";
                                                break;
                                            case "2":
                                                echo "Offboarded";
                                                break;
                                            case "3":
                                                echo "Withdrawn";
                                                break;
                                            case "4":
                                                echo "Extended";
                                                break;
                                            case "5":
                                                echo "Suspended";
                                                break;
                                            case "6":
                                                echo "Terminated";
                                                break;
                                        }
                                    } else {
                                        echo "All Status";
                                    } ?>
                                </button>
                                <ul class="dropdown-menu me-2z" aria-labelledby="dropdownMenuButton1" name="sort">
                                    <li><a class="dropdown-item btn-smoke" <?php
                                        $parameters = "?";
                                        if (!empty($_GET["search"])) {
                                            $parameters = $parameters."search=".$_GET["search"];
                                        }

                                        if (!empty($_GET["department"])) {
                                            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                            $parameters = $parameters."department=".$_GET["department"];
                                        }
                                                
                                        if (!empty($_GET["sort"])) {
                                            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                            $parameters = $parameters."sort=".$_GET["sort"];
                                        }

                                        if (strlen($parameters) > 1) { ?>
                                            href="<?= "interns.php".$parameters ?>" <?php
                                        } else { ?>
                                            href="<?= "interns.php" ?>" <?php
                                        } ?>>All Status</a></li>
                                    <li><a class="dropdown-item btn-smoke" <?php
                                    $parameters = "?";
                                        if (!empty($_GET["search"])) {
                                            $parameters = $parameters."search=".$_GET["search"];
                                        }

                                        if (!empty($_GET["department"])) {
                                            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                            $parameters = $parameters."department=".$_GET["department"];
                                        }

                                        if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                        $parameters = $parameters."status=0";
                                                
                                        if (!empty($_GET["sort"])) {
                                            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                            $parameters = $parameters."sort=".$_GET["sort"];
                                        }

                                        if (strlen($parameters) > 1) { ?>
                                            href="<?= "interns.php".$parameters ?>" <?php
                                        } else { ?>
                                            href="<?= "interns.php" ?>" <?php
                                        } ?>>Inactive</a></li>
                                    <li><a class="dropdown-item btn-smoke" <?php
                                    $parameters = "?";
                                        if (!empty($_GET["search"])) {
                                            $parameters = $parameters."search=".$_GET["search"];
                                        }

                                        if (!empty($_GET["department"])) {
                                            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                            $parameters = $parameters."department=".$_GET["department"];
                                        }

                                        if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                        $parameters = $parameters."status=1";
                                                
                                        if (!empty($_GET["sort"])) {
                                            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                            $parameters = $parameters."sort=".$_GET["sort"];
                                        }

                                        if (strlen($parameters) > 1) { ?>
                                            href="<?= "interns.php".$parameters ?>" <?php
                                        } else { ?>
                                            href="<?= "interns.php" ?>" <?php
                                        } ?>>Active</a></li>
                                    <li><a class="dropdown-item btn-smoke" <?php
                                        $parameters = "?";
                                        if (!empty($_GET["search"])) {
                                            $parameters = $parameters."search=".$_GET["search"];
                                        }

                                        if (!empty($_GET["department"])) {
                                            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                            $parameters = $parameters."department=".$_GET["department"];
                                        }

                                        if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                        $parameters = $parameters."status=2";
                                                
                                        if (!empty($_GET["sort"])) {
                                            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                            $parameters = $parameters."sort=".$_GET["sort"];
                                        }

                                        if (strlen($parameters) > 1) { ?>
                                            href="<?= "interns.php".$parameters ?>" <?php
                                        } else { ?>
                                            href="<?= "interns.php" ?>" <?php
                                        } ?>>Offboarded</a></li>
                                    <li><a class="dropdown-item btn-smoke" <?php
                                        $parameters = "?";
                                        if (!empty($_GET["search"])) {
                                            $parameters = $parameters."search=".$_GET["search"];
                                        }

                                        if (!empty($_GET["department"])) {
                                            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                            $parameters = $parameters."department=".$_GET["department"];
                                        }

                                        if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                        $parameters = $parameters."status=3";
                                                
                                        if (!empty($_GET["sort"])) {
                                            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                            $parameters = $parameters."sort=".$_GET["sort"];
                                        }

                                        if (strlen($parameters) > 1) { ?>
                                            href="<?= "interns.php".$parameters ?>" <?php
                                        } else { ?>
                                            href="<?= "interns.php" ?>" <?php
                                        } ?>>Withdrawn</a></li>
                                    <li><a class="dropdown-item btn-smoke" <?php
                                        $parameters = "?";
                                        if (!empty($_GET["search"])) {
                                            $parameters = $parameters."search=".$_GET["search"];
                                        }

                                        if (!empty($_GET["department"])) {
                                            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                            $parameters = $parameters."department=".$_GET["department"];
                                        }

                                        if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                        $parameters = $parameters."status=4";
                                                
                                        if (!empty($_GET["sort"])) {
                                            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                            $parameters = $parameters."sort=".$_GET["sort"];
                                        }

                                        if (strlen($parameters) > 1) { ?>
                                            href="<?= "interns.php".$parameters ?>" <?php
                                        } else { ?>
                                            href="<?= "interns.php" ?>" <?php
                                        } ?>>Extended</a></li>
                                    <li><a class="dropdown-item btn-smoke" <?php
                                        $parameters = "?";
                                        if (!empty($_GET["search"])) {
                                            $parameters = $parameters."search=".$_GET["search"];
                                        }

                                        if (!empty($_GET["department"])) {
                                            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                            $parameters = $parameters."department=".$_GET["department"];
                                        }

                                        if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                        $parameters = $parameters."status=5";
                                                
                                        if (!empty($_GET["sort"])) {
                                            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                            $parameters = $parameters."sort=".$_GET["sort"];
                                        }

                                        if (strlen($parameters) > 1) { ?>
                                            href="<?= "interns.php".$parameters ?>" <?php
                                        } else { ?>
                                            href="<?= "interns.php" ?>" <?php
                                        } ?>>Suspended</a></li>
                                    <li><a class="dropdown-item btn-smoke" <?php
                                        $parameters = "?";
                                        if (!empty($_GET["search"])) {
                                            $parameters = $parameters."search=".$_GET["search"];
                                        }

                                        if (!empty($_GET["department"])) {
                                            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                            $parameters = $parameters."department=".$_GET["department"];
                                        }

                                        if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                        $parameters = $parameters."status=6";
                                                
                                        if (!empty($_GET["sort"])) {
                                            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                            $parameters = $parameters."sort=".$_GET["sort"];
                                        }

                                        if (strlen($parameters) > 1) { ?>
                                            href="<?= "interns.php".$parameters ?>" <?php
                                        } else { ?>
                                            href="<?= "interns.php" ?>" <?php
                                        } ?>>Terminated</a></li>
                                </ul>
                            </div>
                            <!--SORTING DROPDOWN-->
                            <div class="dropdown me-2">
                                <button class="btn btn-light border-dark dropdown-toggle" type="button" id="dropdownMenuButton1"
                                data-bs-toggle="dropdown" aria-expanded="false"> <?php
                                    if (empty($_GET["sort"])) {
                                        echo "Default";
                                    } else {
                                        switch ($_GET["sort"]) {
                                            case "1":
                                                echo "A-Z";
                                                break;
                                            case "2":
                                                echo "Z-A";
                                                break;
                                            case "3":
                                                echo "Oldest Intern";
                                                break;
                                            case "4":
                                                echo "Newest Intern";
                                                break;
                                        }
                                    } ?>
                                </button>
                                <ul class="dropdown-menu me-2z" aria-labelledby="dropdownMenuButton1" name="sort">
                                    <li><a class="dropdown-item btn-smoke" <?php
                                        $parameters = "?";
                                        if (!empty($_GET["search"])) {
                                            $parameters = $parameters."search=".$_GET["search"];
                                        }

                                        if (!empty($_GET["department"])) {
                                            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                            $parameters = $parameters."department=".$_GET["department"];
                                        }

                                        if (isset($_GET["status"])) {
                                            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                            $parameters = $parameters."status=".$_GET["status"];
                                        }

                                        if (strlen($parameters) > 1) { ?>
                                            href="<?= "interns.php".$parameters ?>" <?php
                                        } else { ?>
                                            href="<?= "interns.php" ?>" <?php
                                        } ?>>Default</a></li>
                                    <li><a class="dropdown-item btn-smoke" <?php
                                    $parameters = "?";
                                        if (!empty($_GET["search"])) {
                                            $parameters = $parameters."search=".$_GET["search"];
                                        }

                                        if (!empty($_GET["department"])) {
                                            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                            $parameters = $parameters."department=".$_GET["department"];
                                        }

                                        if (isset($_GET["status"])) {
                                            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                            $parameters = $parameters."status=".$_GET["status"];
                                        }

                                        if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                        $parameters = $parameters."sort=1";

                                        if (strlen($parameters) > 1) { ?>
                                            href="<?= "interns.php".$parameters ?>" <?php
                                        } else { ?>
                                            href="<?= "interns.php" ?>" <?php
                                        } ?>>A-Z</a></li>
                                    <li><a class="dropdown-item btn-smoke" <?php
                                    $parameters = "?";
                                        if (!empty($_GET["search"])) {
                                            $parameters = $parameters."search=".$_GET["search"];
                                        }

                                        if (!empty($_GET["department"])) {
                                            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                            $parameters = $parameters."department=".$_GET["department"];
                                        }

                                        if (isset($_GET["status"])) {
                                            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                            $parameters = $parameters."status=".$_GET["status"];
                                        }
                                        
                                        if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                        $parameters = $parameters."sort=2";

                                        if (strlen($parameters) > 1) { ?>
                                            href="<?= "interns.php".$parameters ?>" <?php
                                        } else { ?>
                                            href="<?= "interns.php" ?>" <?php
                                        } ?>>Z-A</a></li>
                                    <li><a class="dropdown-item btn-smoke" <?php
                                        $parameters = "?";
                                        if (!empty($_GET["search"])) {
                                            $parameters = $parameters."search=".$_GET["search"];
                                        }

                                        if (!empty($_GET["department"])) {
                                            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                            $parameters = $parameters."department=".$_GET["department"];
                                        }

                                        if (isset($_GET["status"])) {
                                            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                            $parameters = $parameters."status=".$_GET["status"];
                                        }
                                        
                                        if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                        $parameters = $parameters."sort=3";

                                        if (strlen($parameters) > 1) { ?>
                                            href="<?= "interns.php".$parameters ?>" <?php
                                        } else { ?>
                                            href="<?= "interns.php" ?>" <?php
                                        } ?>>Oldest Intern</a></li>
                                    <li><a class="dropdown-item btn-smoke" <?php
                                        $parameters = "?";
                                        if (!empty($_GET["search"])) {
                                            $parameters = $parameters."search=".$_GET["search"];
                                        }

                                        if (!empty($_GET["department"])) {
                                            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                            $parameters = $parameters."department=".$_GET["department"];
                                        }

                                        if (isset($_GET["status"])) {
                                            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                            $parameters = $parameters."status=".$_GET["status"];
                                        }
                                        
                                        if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                        $parameters = $parameters."sort=4";

                                        if (strlen($parameters) > 1) { ?>
                                            href="<?= "interns.php".$parameters ?>" <?php
                                        } else { ?>
                                            href="<?= "interns.php" ?>" <?php
                                        } ?>>Newest Intern</a></li>
                                </ul>
                            </div>
                        </div> <?php
                        if ($admin_roles_count != 0) { ?>
                            <div class="modal fade" id="addInternModal" tabindex="-1" aria-labelledby="addInternModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="addInternModalLabel">Add Intern</h5>
                                            <button class="btn btn-danger btn-sm text-light" data-bs-dismiss="modal">
                                                <i class="fa-solid fa-close"></i>
                                            </button>
                                        </div>

                                        <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="post">
                                            <div class="modal-body">
                                                <div class="row">
                                                    <!-- <div class="col-6 user_input my-1">
                                                        <label class="mb-2" for="intern_id">Intern ID</label>
                                                        <div class="input-group">
                                                            <input type="text" name="intern_id" class="form-control" disabled>
                                                            <div class="input-group-append">
                                                                <button type="button" class="btn btn-smoke">Regen</button>
                                                            </div>
                                                        </div>
                                                    </div> -->
                                                    <div class="col-12 user_input my-1">
                                                        <label class="mb-2" for="lastName">Last Name
                                                            <span class="text-danger">*</span>
                                                        </label>
                                                        <input type="text" name="lastName" class="form-control" maxLength="32">
                                                    </div>
                                                    <div class="col-12 user_input my-1">
                                                        <label class="mb-2" for="firstName">First Name
                                                            <span class="text-danger">*</span>
                                                        </label>
                                                        <input type="text" name="firstName" class="form-control" maxLength="32">
                                                    </div>
                                                    <div class="col-12 user_input my-1">
                                                        <label class="mb-2" for="middleName">Middle Name</label>
                                                        <input type="text" name="middleName" class="form-control" maxLength="32">
                                                    </div>
                                                    <div class="col-12 user_input my-1">
                                                        <label class="mb-2" for="gender">Gender</label>
                                                        <select name="gender" class="form-select">
                                                            <option value="0">Male</option>
                                                            <option value="1">Female</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-12 user_input my-1">
                                                        <label class="mb-2" for="department">Department</label>
                                                        <select name="department" class="form-select"> <?php
                                                            $db->query("SELECT * FROM departments ORDER BY name");
                                                            $db->execute();
                                                            
                                                            while ($row = $db->fetch()) { ?>
                                                                <option value="<?= $row["id"] ?>"><?= $row["name"] ?> </option> <?php
                                                            } ?>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="modal-footer">
                                                <button type="submit" name="addIntern" class="btn btn-success">Submit</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="w-fit ms-auto">
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" 
                                    data-bs-target="#addInternModal">
                                    <i class="fa-solid fa-plus me-2"></i>Add Intern
                                </button>
                                <a class="btn btn-secondary" href="edit_interns_profile.php">
                                    <i class="fa-solid fa-pen me-2"></i>Edit Profile
                                </a>
                            </div> <?php
                        } ?>
                    </div>
                </div>
            </div>
        </div> <?php
        if (isset($_SESSION["success"])) { ?>
            <div class="alert alert-success text-success">
                <?php
                    echo $_SESSION["success"];
                    unset($_SESSION["success"]);
                ?>
            </div> <?php
        }

        if (isset($_SESSION["failed"])) { ?>
            <div class="alert alert-danger text-danger">
                <?php
                    echo $_SESSION["failed"];
                    unset($_SESSION["failed"]);
                ?>
            </div> <?php
        } ?>
        <div class="row"> <?php
            if ($admin_roles_count != 0) { ?>
                <div class="rounded shadow px-0 mb-2">
                    <h6 class="d-block text-light px-3 pt-2 pb-2 rounded mb-0" style="background: #0D0048;">
                        Unactivated Accounts
                    </h6>
                </div>

                <div class="interns"> <?php
                    $sort = " ORDER BY last_name LIMIT 5";

                    $conditions = " WHERE intern_personal_information.id = intern_wsap_information.id AND
                    intern_wsap_information.department_id = departments.id AND
                    (SELECT COUNT(*) FROM intern_accounts WHERE intern_accounts.id=intern_personal_information.id) = 0";

                    if (!empty($_GET["search"])) {
                        if (strlen($conditions) > 6) {
                            $conditions = $conditions." AND";
                        }
                        $conditions = $conditions." (CONCAT(last_name, ' ', first_name) LIKE CONCAT( '%', :intern_name, '%') OR
                        CONCAT(first_name, ' ', last_name) LIKE CONCAT( '%', :intern_name, '%'))";
                    }
                    if (!empty($_GET["department"])) {
                        if (strlen($conditions) > 6) {
                            $conditions = $conditions." AND";
                        }
                        $conditions = $conditions." departments.name=:dept_name";
                    }
                    if (isset($_GET["status"])) {
                        if (strlen($conditions) > 6) {
                            $conditions = $conditions." AND";
                        }
                        $conditions = $conditions." intern_wsap_information.status=:status";
                    }

                    $query = "SELECT intern_personal_information.id AS intern_id, intern_personal_information.*, intern_wsap_information.*, departments.*
                    FROM intern_personal_information, intern_wsap_information, departments";

                    if (strlen($conditions) > 6) {
                        $db->query($query.$conditions.$sort);
    
                        if (!empty($_GET["search"])) {
                            $db->selectInternName($_GET["search"]);
                        }
                        if (!empty($_GET["department"])) {
                            $db->selectDepartment($_GET["department"]);
                        }
                        if (isset($_GET["status"])) {
                            $db->selectStatus($_GET["status"]);
                        }
                    }
                    $db->execute();

                    $count = 0;
                    while ($row = $db->fetch()) {
                        $count++; ?>
                        <div class="modal fade" id="removeAccountModal<?= $row["intern_id"] ?>" tabindex="-1"
                            aria-labelledby="removeAccountModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="removeAccountModalLabel">Remove Account</h5>
                                        <button class="btn btn-danger btn-sm text-light" data-bs-dismiss="modal">
                                            <i class="fa-solid fa-close"></i>
                                        </button>
                                    </div>
                                    
                                    <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="post">
                                        <div class="modal-body">
                                            <div class="intern text-center">
                                                <div class="top">
                                                    <img class="img-intern mx-auto" src="<?php {
                                                        if ($row["gender"] == 0) {
                                                            echo "../Assets/img/profile_imgs/default_male.png";
                                                        } else {
                                                            echo "../Assets/img/profile_imgs/default_female.png";
                                                        }
                                                    } ?>" onerror="this.src='../Assets/img/no_image_found.jpeg';">
                                                </div>
                                                <div class="summary-total mt-2 w-fit mx-auto">
                                                    <h5 class="text-dark fs-regular mb-0">
                                                        <?= $row["last_name"].", ".$row["first_name"] ?>
                                                    </h5>
                                                    <h6 class="fs-f mb-0"><?= $row["name"] ?></h6>
                                                    <h6 class="fs-d fw-bold"><?= $row["intern_id"] ?></h6>
                                                    <input type="text" name="intern_id" class="form-control text-center d-none"
                                                        value="<?= $row["intern_id"] ?>" readonly>
                                                    <input type="text" name="fullName" class="form-control text-center d-none"
                                                        value="<?= $row["last_name"].", ".$row["first_name"] ?>" readonly>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="modal-footer">
                                            <button type="submit" name="removeAccount" class="btn btn-danger">Remove</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="limited-card position-relative <?php
                            if ($count == 5) {
                                echo "d-xxl-block";
                            }
                            if ($count == 4) {
                                echo "d-xl-block";
                            }
                            if ($count == 3) {
                                echo "d-lg-block";
                            }
                            if ($count == 2) {
                                echo "d-md-block";
                            }
                            if ($count == 1) {
                                echo "d-block";
                            } ?>">
                            <div class="intern text-center">
                                <div class="top">
                                    <img class="img-intern mx-auto" src="<?php
                                        if ($row["gender"] == 0) {
                                            echo "../Assets/img/profile_imgs/default_male.png";
                                        } else {
                                            echo "../Assets/img/profile_imgs/default_female.png";
                                        } ?>" onerror="this.src='../Assets/img/no_image_found.jpeg';">
                                </div>
                                <div class="summary-total mt-2 w-fit mx-auto">
                                    <h5 class="mb-0 text-dark fs-regular">
                                        <?= $row["last_name"].", ".$row["first_name"] ?>
                                    </h5>
                                    <h6 class="fs-f mb-0"><?= $row["name"] ?></h6>
                                    <h6 class="fs-d fw-bold"><?= $row["intern_id"] ?></h6>
                                </div>
                            </div>
                            <button class="btn btn-danger btn-sm top-right" data-bs-toggle="modal" 
                                data-bs-target="#removeAccountModal<?= $row["intern_id"] ?>">
                                <i class="fa-solid fa-xmark fs-c"></i>
                            </button>
                        </div> <?php
                    } ?>
                </div> <?php
                if ($db->rowCount() == 0) { ?>
                    <div class="w-100 text-center my-5">
                        <h3>No Record</h3>
                    </div> <?php
                } else { ?>
                    <a class="btn btn-secondary w-fit mx-auto" href="unactivated_accounts.php">
                        Show All<i class="fa-solid fa-arrow-right ms-2"></i>
                    </a> <?php
                }
            } ?>
            
            <div class="rounded shadow px-0 mt-3 mb-2">
                <h6 class="d-block text-light px-3 pt-2 pb-2 rounded mb-0" style="background: #0D0048;">
                    Activated Accounts
                </h6>
            </div>

            <div class="interns"> <?php
                $sort = " ORDER BY intern_personal_information.last_name";
                if (!empty($_GET["sort"])) {
                    switch ($_GET["sort"]) {
                        case "1":
                            $sort = " ORDER BY intern_personal_information.last_name";
                            break;
                        case "2":
                            $sort = " ORDER BY intern_personal_information.last_name DESC";
                            break;
                        case "3":
                            $sort = " ORDER BY intern_wsap_information.onboard_date";
                            break;
                        case "4":
                            $sort = " ORDER BY intern_wsap_information.onboard_date DESC";
                            break;
                    }
                }

                $conditions = " WHERE intern_personal_information.id = intern_wsap_information.id AND
                intern_personal_information.id = intern_accounts.id AND
                intern_wsap_information.department_id = departments.id";

                if (!empty($_GET["search"])) {
                    if (strlen($conditions) > 6) {
                        $conditions = $conditions." AND";
                    }
                    $conditions = $conditions." (CONCAT(last_name, ' ', first_name) LIKE CONCAT( '%', :intern_name, '%') OR
                    CONCAT(first_name, ' ', last_name) LIKE CONCAT( '%', :intern_name, '%'))";
                }
                if (!empty($_GET["department"])) {
                    if (strlen($conditions) > 6) {
                        $conditions = $conditions." AND";
                    }
                    $conditions = $conditions." departments.name=:dept_name";
                }
                if (isset($_GET["status"])) {
                    if (strlen($conditions) > 6) {
                        $conditions = $conditions." AND";
                    }
                    $conditions = $conditions." intern_wsap_information.status=:status";
                }

                $query = "SELECT intern_personal_information.id AS intern_id, intern_personal_information.*, 
                intern_wsap_information.*, intern_accounts.*,  departments.*
                FROM intern_personal_information, intern_wsap_information, intern_accounts, departments";

                if (strlen($conditions) > 6) {
                    $db->query($query.$conditions.$sort);

                    if (!empty($_GET["search"])) {
                        $db->selectInternName($_GET["search"]);
                    }
                    if (!empty($_GET["department"])) {
                        $db->selectDepartment($_GET["department"]);
                    }
                    if (isset($_GET["status"])) {
                        $db->selectStatus($_GET["status"]);
                    }
                }
                $db->execute();

                while ($row = $db->fetch()) {
                    if ($admin_roles_count != 0) { ?>
                        <a class="clickable-card" href="profile.php?intern_id=<?= $row["intern_id"] ?>"
                            draggable="false"> <?php
                    } ?>
                            <div class="h-100 intern text-center position-relative pb-5">
                                <div class="top" style="height: 100px;">
                                    <img class="img-intern mx-auto" src="<?php {
                                        if ($row["image"] == null || strlen($row["image"]) == 0) {
                                            if ($row["gender"] == 0) {
                                                echo "../Assets/img/profile_imgs/default_male.png";
                                            } else {
                                                echo "../Assets/img/profile_imgs/default_female.png";
                                            }
                                        } else {
                                            echo $row["image"];
                                        }
                                    } ?>" onerror="this.src='../Assets/img/no_image_found.jpeg';">
                                </div>
                                <div class="summary-total mt-2 w-fit mx-auto">
                                    <h5 class="mb-0 text-dark fs-regular">
                                        <?= $row["last_name"].", ".$row["first_name"] ?>
                                    </h5>
                                    <h6 class="fs-f"><?= $row["name"] ?></h6>
                                </div>
                                <div class="absolute-bottom absolute-w-100 py-3 d-flex justify-content-center" style="bottom: 0;"> <?php
                                    if ($row["status"] == 0 || $row["status"] == 5) { ?>
                                        <p class="bg-warning text-dark rounded w-fit m-auto px-2 py-1 fs-d"> <?php
                                            if ($row["status"] == 0) {
                                                echo "Inactive";
                                            } else {
                                                echo "Suspended";
                                            } ?>
                                        </p> <?php
                                    } else if ($row["status"] == 1 || $row["status"] == 4) { ?>
                                        <p class="bg-success text-light rounded w-fit m-auto px-2 py-1 fs-d"> <?php
                                            if ($row["status"] == 1) {
                                                echo "Active";
                                            } else {
                                                echo "Extended";
                                            } ?>
                                        </p> <?php
                                    } else if ($row["status"] == 2) { ?>
                                        <p class="bg-secondary text-light rounded w-fit m-auto px-2 py-1 fs-d">
                                            Offboarded
                                        </p> <?php
                                    } else if ($row["status"] == 3) { ?>
                                        <p class="bg-dark text-light rounded w-fit m-auto px-2 py-1 fs-d">
                                            Withdrawn
                                        </p> <?php
                                    } else if ($row["status"] == 6) { ?>
                                        <p class="bg-danger text-light rounded w-fit m-auto px-2 py-1">
                                            Terminated
                                        </p> <?php
                                    } ?>
                                </div>
                            </div> <?php
                    if ($admin_roles_count != 0) { ?>
                        </a> <?php
                    }
                } ?>
            </div> <?php
            if ($db->rowCount() == 0) { ?>
                <div class="w-100 text-center my-5">
                    <h3>No Record</h3>
                </div> <?php
            } ?>
        </div>
        
    </div>
</div>
<?php
    require_once "../Templates/footer.php";
?>