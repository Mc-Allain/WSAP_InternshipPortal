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
    $admin_roles_count = $db->rowCount();

    $selected_date = $date->getNumericDate();
    if (!empty($_GET["date"])) {
        $selected_date = $_GET["date"];
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

        if (!empty($selected_date)) {
            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
            $parameters = $parameters."date=".$selected_date;
        }
        
        if (!empty($_GET["sort"])) {
            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
            $parameters = $parameters."sort=".$_GET["sort"];
        }

        if (strlen($parameters) > 1) {
            redirect("no_time_out_interns.php".$parameters);
        } else {
            redirect("no_time_out_interns.php");
        }

        exit();
    }

    if (isset($_POST["reset"])) {
        if (!empty($selected_date)) {
            redirect("no_time_out_interns.php?date=".$selected_date);
        } else {  
            redirect("no_time_out_interns.php");
        }
        exit();
    }

    require_once "../Templates/header_view.php";
    setTitle("No Time out Interns");
?>
<div class="my-container"> 
    <?php
        include_once "nav_side_bar.php";
        navSideBar("attendance");
    ?>
    <div class="main-section p-4">
        <div class="aside">
            <?php include_once "profile_nav.php"; ?>
        </div>
        
        <div class="d-flex align-items-center mb-2">
            <div>
                <h3>No Time out Interns</h3>
            </div>
        </div> <?php

        if ($admin_roles_count != 0) { ?>
            <div>
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
                    </form>
                        
                    <div class="col-12 d-xl-flex d-lg-inline-block">
                        <div class="mb-2">
                            <a class="btn btn-secondary me-2" href="interns_attendance.php?date=<?= $selected_date ?>">
                                <i class="fa-solid fa-arrow-left me-2"></i>Back to Interns' Attendance
                            </a>
                        </div>

                        <div class="d-md-flex d-sm-inline-block">
                            <div class="w-md-fit w-sm-100 d-flex align-items-center mb-2 me-md-2 me-sm-0">
                                <div class="input-group">
                                    <input type="text" class="form-control" value="<?= date("F j, Y", strtotime($selected_date)) ?>" disabled>
                                    <div class="input-group-append">
                                        <a class="btn btn-smoke" href="calendar.php?destination=no_time_out_interns">Select Date</a>
                                    </div>
                                </div>
                            </div>

                            <!--DEPARTMENT DROPDOWN-->
                            <div class="w-fit d-flex mb-2">
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

                                            if (!empty($selected_date)) {
                                                if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                $parameters = $parameters."date=".$selected_date;
                                            }
                                            
                                            if (!empty($_GET["sort"])) {
                                                if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                $parameters = $parameters."sort=".$_GET["sort"];
                                            }

                                            if (strlen($parameters) > 1) { ?>
                                                href="<?= "no_time_out_interns.php".$parameters ?>" <?php
                                            } else { ?>
                                                href="<?= "no_time_out_interns.php" ?>" <?php
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

                                                if (!empty($selected_date)) {
                                                    if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                    $parameters = $parameters."date=".$selected_date;
                                                }
                                                
                                                if (!empty($_GET["sort"])) {
                                                    if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                    $parameters = $parameters."sort=".$_GET["sort"];
                                                }

                                                if (strlen($parameters) > 1) { ?>
                                                    href="<?= "no_time_out_interns.php".$parameters ?>" <?php
                                                } else { ?>
                                                    href="<?= "no_time_out_interns.php" ?>" <?php
                                                } ?>> <?= $row["name"] ?>
                                            </a></li> <?php
                                        } ?>
                                    </ul>
                                </div>
                                <!--SORTING DROPDOWN-->
                                <div class="dropdown">
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
                                            }
                                        } ?>
                                    </button>
                                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1" name="sort">
                                        <li><a class="dropdown-item btn-smoke" <?php
                                            $parameters = "?";
                                            if (!empty($_GET["search"])) {
                                                $parameters = $parameters."search=".$_GET["search"];
                                            }

                                            if (!empty($_GET["department"])) {
                                                if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                $parameters = $parameters."department=".$_GET["department"];
                                            }

                                            if (!empty($selected_date)) {
                                                if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                $parameters = $parameters."date=".$selected_date;
                                            }

                                            if (strlen($parameters) > 1) { ?>
                                                href="<?= "no_time_out_interns.php".$parameters ?>" <?php
                                            } else { ?>
                                                href="<?= "no_time_out_interns.php" ?>" <?php
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

                                            if (!empty($selected_date)) {
                                                if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                $parameters = $parameters."date=".$selected_date;
                                            }

                                            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                            $parameters = $parameters."sort=1";

                                            if (strlen($parameters) > 1) { ?>
                                                href="<?= "no_time_out_interns.php".$parameters ?>" <?php
                                            } else { ?>
                                                href="<?= "no_time_out_interns.php" ?>" <?php
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

                                            if (!empty($selected_date)) {
                                                if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                                $parameters = $parameters."date=".$selected_date;
                                            }
                                            
                                            if (strlen($parameters) > 1) { $parameters = $parameters."&"; }
                                            $parameters = $parameters."sort=2";

                                            if (strlen($parameters) > 1) { ?>
                                                href="<?= "no_time_out_interns.php".$parameters ?>" <?php
                                            } else { ?>
                                                href="<?= "no_time_out_interns.php" ?>" <?php
                                            } ?>>Z-A</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="w-fit ms-auto">
                        <button class="btn btn-secondary mb-1" onclick="copyRecords()">
                            Copy Records as Text
                        </button>
                    </div>
                </div>
            </div>
            <div class="row mb-3">
                <div class="interns-attendance"> <?php
                        $sort = " ORDER BY intern_personal_information.last_name";
                        if (!empty($_GET["sort"])) {
                            switch ($_GET["sort"]) {
                                case "1":
                                    $sort = " ORDER BY intern_personal_information.last_name";
                                    break;
                                case "2":
                                    $sort = " ORDER BY intern_personal_information.last_name DESC";
                                    break;
                            }
                        }

                        $conditions = " WHERE intern_personal_information.id = intern_wsap_information.id AND
                        intern_personal_information.id = intern_accounts.id AND
                        intern_wsap_information.department_id = departments.id AND
                        intern_personal_information.id = attendance.intern_id AND
                        att_date=:att_date AND (time_out=:time_out OR time_out IS NULL)";
    
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
    
                        $query = "SELECT intern_personal_information.*, intern_personal_information.id AS intern_id,
                        intern_wsap_information.*, intern_accounts.*, departments.*, attendance.*
                        FROM intern_personal_information, intern_wsap_information, intern_accounts, departments, attendance";
    
                        if (strlen($conditions) > 6) {
                            $db->query($query.$conditions.$sort);
                            $db->setAttDate($selected_date);
                            $db->selectTimeOut("NTO");
        
                            if (!empty($_GET["search"])) {
                                $db->selectInternName($_GET["search"]);
                            }
                            if (!empty($_GET["department"])) {
                                $db->selectDepartment($_GET["department"]);
                            }
                        }
                        $db->execute();

                        $text = "\"No Time out Interns: ".$selected_date."\\n\\n\"\n";

                        if (empty($_GET["department"])) {
                            $text .= "+ \"All Departments:\\n\"\n";
                        } else {
                            $text .= "+ \"".$_GET["department"]." Department:\\n\"\n";
                        }

                        while ($row = $db->fetch()) {
                            $text .= "+ \"".$row["last_name"].", ".$row["first_name"]." - ".$row["intern_id"];
                            if (empty($_GET["department"])) {
                                $text .= " - ".$row["name"]."\\n\"\n";
                            } else {
                                $text .= "\\n\"\n";
                            } ?>
                            <a class="clickable-card" href="daily_time_record.php?intern_id=<?= $row["intern_id"] ?>" draggable="false">
                                <div class="h-100 attendance text-center position-relative" style="padding-bottom: 5rem;">
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
                                    <div class="absolute-bottom absolute-w-100 py-3 d-flex justify-content-center" style="bottom: 0;">
                                        <div>
                                            <p class="m-0 fw-bold fs-e">Time in</p>
                                            <div class="d-flex align-items-center"> <?php
                                                if (strlen($row["time_in"]) > 0) {
                                                    if (isAU($row["time_in"])) { ?>
                                                        <p class="bg-danger text-light rounded w-fit mx-auto fs-d px-2 py-1">
                                                            <?= $row["time_in"] ?>
                                                        </p> <?php
                                                    } else if (isAE($row["time_in"])) { ?>
                                                        <p class="bg-primary text-light rounded w-fit mx-auto fs-d px-2 py-1">
                                                            <?= $row["time_in"] ?>
                                                        </p> <?php
                                                    } else if (strlen($row["time_out"]) > 0 && isMS($row["time_out"]) && !isL($row["time_in"])) { ?>
                                                        <p class="bg-morning text-light rounded w-fit mx-auto fs-d px-2 py-1">
                                                            <?= $row["time_in"] ?>
                                                        </p> <?php
                                                    } else if (strlen($row["time_out"]) > 0 && isAS($row["time_out"]) && !isL($row["time_in"])) { ?>
                                                        <p class="bg-afternoon text-light rounded w-fit mx-auto fs-d px-2 py-1">
                                                            <?= $row["time_in"] ?>
                                                        </p> <?php
                                                    } else if (isOD($row["time_in"])) { ?>
                                                        <p class="bg-dark text-light rounded w-fit mx-auto fs-d px-2 py-1">
                                                            <?= $row["time_in"] ?>
                                                        </p> <?php
                                                    } else if (isL($row["time_in"])) { ?>
                                                        <p class="bg-warning text-dark rounded w-fit mx-auto fs-d px-2 py-1">
                                                            <?= $row["time_in"] ?>
                                                        </p> <?php
                                                    } else { ?>
                                                        <p class="bg-success text-light rounded w-fit mx-auto fs-d px-2 py-1">
                                                            <?= $row["time_in"] ?>
                                                        </p> <?php
                                                    }
                                                } ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a> <?php
                        } ?>
                </div> <?php
                if ($db->rowCount() == 0) { 
                    $text .= "+ \"No Record\"\n"; ?>
                    <div class="w-100 text-center my-5">
                        <h3>No Record</h3>
                    </div> <?php
                } ?>
            </div> <?php
        } else {
            include_once "access_denied.php";
        } ?>        
    </div>
</div>
<?php
    require_once "../Controllers/PHP_JS.php";
    copyFunction($text);
    require_once "../Templates/footer.php";
?>