<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GHAP RO-Crate</title>
    <style>
        body {
            background-color: #ffffff;
            color: #606060;
            font-family: Arial, Helvetica, sans-serif;
        }

        a {
            color: #606060;
        }

        .container {
            margin-left: auto;
            margin-right: auto;
        }

        /* Larger than mobile */
        @media (min-width: 400px) {
            .container {
                width: 98%;
            }
        }

        /* Larger than phablet */
        @media (min-width: 550px) {
            .container {
                width: 95%;
            }
        }

        /* Larger than tablet */
        @media (min-width: 750px) {
            .container {
                width: 90%;
            }
        }

        /* Larger than desktop */
        @media (min-width: 1000px) {
            .container {
                width: 85%;
            }
        }

        /* Larger than Desktop HD */
        @media (min-width: 1200px) {
            .container {
                width: 1060px;
            }
        }

        table.info {
            border-collapse: collapse;
            border: none;
            width: 100%;
            margin-bottom: 3rem;
        }

        table.info th, table.info td {
            text-align: left;
            padding: 1rem;
        }

        table.info tr:nth-child(odd) {
            background-color: #f2f2f2;
        }

        table.info tr {
            border-top: 1px solid #ccc;
            border-bottom: 1px solid #ccc;
        }

        table.info tr th:first-child {
            border-right: 1px solid #ccc;
        }

        table.info th:first-child {
            width: 30%;
        }
    </style>
    <script type="application/ld+json">
        {!! json_encode($metadata) !!}
    </script>
</head>
<body>
<div class="container">
    @yield('content')
</div>
</body>
</html>
