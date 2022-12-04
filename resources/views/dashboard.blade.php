<!DOCTYPE html>
<html lang="en">

<!--
    * This file is part of the Laravel NOWPayments package.
    *
    * (c) Prevail Ejimadu <prevailexcellent@gmail.com>
    *
    * For the full copyright and license information, please view the LICENSE
    * file that was distributed with this source code.
    */ 
-->

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Laravel NOWPayment</title>
    <link href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.0/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
    <script src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.0/js/bootstrap.min.js"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css">
    <link href="http://fontawesome.io/assets/font-awesome/css/font-awesome.css" rel="stylesheet" media="screen">

    <style>
        body {
            background-color: #dedede;
        }

        .topbar {
            background: #2A3F54;
            border-color: #2A3F54;
            border-radius: 0px;
        }

        .topbar .navbar-header a {
            color: #ffffff;
        }

        .wrapper {
            padding-left: 0px;
            -webkit-transition: all 0.5s ease;
            -moz-transition: all 0.5s ease;
            -o-transition: all 0.5s ease;
            transition: all 0.5s ease;
        }

        .main {
            width: 100%;
            position: relative;
            padding-bottom: 20px;
        }

        .content {
            margin-top: 70px;
            padding: 0 30px;
        }
    </style>

    <script src="//code.jquery.com/jquery-1.11.1.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>

</head>

<body>
    <nav class="navbar navbar-default navbar-fixed-top topbar">
        <div class="container-fluid">

            <div class="navbar-header">

                <a href="#" class="navbar-brand">
                    <span class="visible-xs">Laravel NOWPayments</span>
                    <span class="hidden-xs">Laravel NOWPayments</span>
                </a>

                <p class="navbar-text">
                    <a href="#" class="sidebar-toggle">
                        <i class="fa fa-bars"></i>
                    </a>
                </p>

            </div>
        </div>
    </nav>

    <article class="wrapper">
        <section class="main">
            <section class="tab-content">
                <section class="tab-pane active fade in content" id="dashboard">
                    <div class="row">
                        <div class="col-xs-6 col-sm-2">
                            <div class="panel panel-primary border">
                                <div class="panel-body">
                                    <h5>API Status: <b>ON</b></h5>
                                </div>
                            </div>
                        </div>

                        <div class="col-xs-6 col-sm-2 offset-sm-10">
                            <div class="panel panel-primary border">
                                <div class="panel-body">
                                    <h5>Enviroment: <b
                                            style="text-transform: capitalize">{{ config('nowpayments.env') }}</b></h5>
                                </div>
                            </div>
                        </div>

                        <div class="col-xs-12 col-sm-9">
                            <div class="panel panel-primary">
                                <div class="panel-heading">
                                    <h4><b>Payment List</b></h4>
                                </div>
                                <div class="panel-body">
                                    <div class="table-responsive">
                                        <table id="pList" class="table table-sm table-hover table-striped">
                                            <thead>
                                                <tr>
                                                    <th scope="col">Payment ID</th>
                                                    <th scope="col">Order ID</th>
                                                    <th scope="col">Original Price</th>
                                                    <th scope="col">Amount Sent</th>
                                                    <th scope="col">Amount Received</th>
                                                    <th scope="col">Status</th>
                                                    <th scope="col">Address</th>
                                                    <th scope="col">Created at</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($payments as $pay)
                                                    <tr>
                                                        <th scope="row">{{ $pay['payment_id'] }}</th>
                                                        <td>{{ $pay['order_id'] }}</td>
                                                        <td>{{ $pay['price_amount'] }} {{ $pay['price_currency'] }}</td>
                                                        <td>{{ $pay['pay_amount'] }} {{ $pay['pay_currency'] }}</td>
                                                        <td>{{ $pay['outcome_amount'] }} {{ $pay['outcome_currency'] }}
                                                        </td>
                                                        <td>{{ $pay['payment_status'] }}</td>
                                                        <td>{{ $pay['pay_address'] }}</td>
                                                        <td>{{ Carbon\Carbon::parse($pay['created_at'])->format('d M Y, H:i A') }}
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <div class="alert alert-info">No Payments initiated yet</div>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xs-12 col-sm-3">
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    <b>API Log</b>
                                </div>
                                <ul class="list-group">
                                    @foreach ($logs as $log)
                                        <li class="list-group-item">{{ $log->endpoint }} <span
                                                class="float-right badge badge-secondary">{{ $log->count }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>

                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    What's New in Laravel NOWPayments
                                </div>
                                <div class="panel-body">
                                    Docs here on <a href="https://github.com/PrevailExcel/laravel-nowpayments"
                                        target="_blank">Github</a><br>
                                    Version: v{{ $version }} <br>
                                    <a href="https://www.buymeacoffee.com/prevail" target="_blank">Buy Me A Coffee</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </section>
        </section>
    </article>

    <script type="text/javascript">
        $(document).ready(function() {
            // DataTable
            $('#pList').DataTable()
        });
    </script>
</body>

</html>
