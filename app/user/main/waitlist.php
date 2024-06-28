<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="table.css">
    <title>待ち時間一括表示させるとこ</title>
    <style>
        h1 {
            font-size: 40px;
            text-align: center;
            white-space: nowrap;
        }
        body {
            font-family: "Open Sans", sans-serif;
            line-height: 1.25;
        }

        table {
            border-collapse: collapse;
            margin: 0 auto;
            padding: 0;
            width: 400px;
            table-layout: fixed;
            color: #000;
            font-size: 20px;
        }

        table tr {
            background-color: #fff;
            padding: .35em;
            border-bottom: 1px solid #bbb;
        }

        table thead {
            font-size: larger;
            border-bottom: 5px solid #ff9900;
        }

        table tr:last-child {
            border-bottom: none
        }

        table th,
        table td {
            padding: 1em 10px 1em 1em;
            border-right: 1px solid #bbb;
            width: 150px;
        }

        table th:last-child,
        table td:last-child {
            border: none;
        }

        tbody th {
            color: #ff9901;
        }

        .txt {
            text-align: left;
            font-size: .85em;
        }

        .price {
            text-align: right;
        }

        @media screen and (max-width: 600px) {
            table {
                border: 0;
                width: 100%
            }

            table th {
                display: block;
                border-right: none;
                border-bottom: 5px solid #ff9901;
                padding-bottom: .6em;
                margin-bottom: .6em;

            }

            table thead {
                border: none;
                clip: rect(0 0 0 0);
                height: 1px;
                margin: -1px;
                overflow: hidden;
                padding: 0;
                position: absolute;
                width: 1px;
            }

            table tr {
                display: block;
                margin-bottom: 2em;
                border-bottom: 1px solid skyblue;
            }

            table td {
                border-bottom: 1px solid skyblue;
                display: block;
                font-size: .8em;
                text-align: right;
                position: relative;
                padding: .625em .100em .625em 6em;
                border-right: none;
            }

            table td::before {
                content: attr(data-label);
                font-weight: bold;
                position: absolute;
                left: 10px;
            }

            table td:last-child {
                border-bottom: 0;
            }
        }
    </style>
</head>
<h1>待ち時間一覧</h1>

<body>
    
<a href="index.php">メインページへ</a>
    <table>
        <thead>
            <tr>
                <th scope="col">クラス</th>
                <th scope="col">待ち時間</th>
            </tr>
        </thead>
        <tbody>
            <?php
            require_once 'config.php';

            try {
                $pdo = new PDO($dsn, $user, $password);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $stmt = $pdo->prepare('SELECT classname, expecttime FROM class');
                $stmt->execute();
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($rows as $row) {
                    $classwaittime = ceil($row['expecttime'] / 60);
                    echo "<tr>";
                    echo "<td data-label='クラス' class='txt'>" . htmlspecialchars($row['classname']) . "</td>";
                    echo "<td data-label='待ち時間' class='txt'>" . htmlspecialchars($classwaittime) . "分</td>";
                    echo "</tr>";
                }
            } catch (PDOException $e) {
                echo $e->getMessage();
                exit;
            }
            ?>
        </tbody>
    </table>
</body>

</html>