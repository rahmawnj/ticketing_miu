<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>miu ticketing system</title>

    <link href="{{ asset('/') }}css/vendor.min.css" rel="stylesheet" />
    <link href="{{ asset('/') }}css/apple/app.min.css" rel="stylesheet" />
    <link href="{{ asset('/') }}plugins/ionicons/css/ionicons.min.css" rel="stylesheet" />
</head>

<body>
    <div class="container">
        <div class="row mt-5 mb-3">
            <div class="col-xl-12 mb-3">
                <h1 class="text-center fs-48px text-uppercase">selamat datang</h1>
            </div>
        </div>

        
        <div class="row d-flex justify-content-center">
            <div class="col-xl-10">
                <div class="card">
                    <div class="card-body p-5 row d-flex justify-content-between">
                        <div class="col-md-4">
                            <img src="{{ asset('/img/no-image.jpg') }}" alt="" id="image-preview" class="img-fluid">
                        </div>

                        <div class="col-md-6">
                            <dl class="fs-18px">
                                <dt class="text-inverse">Name</dt>
                                <dd id="info-name">-</dd>
                                <dt class="text-inverse">Membership</dt>
                                <dd id="info-membership">-</dd>
                                <dt class="text-inverse">Expired At</dt>
                                <dd id="info-expired">-</dd>
                                <dt class="text-inverse">Status</dt>
                                <dd id="info-status">-</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('/') }}js/vendor.min.js"></script>
    <script src="{{ asset('/') }}js/app.min.js"></script>
    <script src="{{ asset('/') }}js/theme/apple.min.js"></script>

    <script>
        $(document).ready(function() {
            function getLastMember() {
                $.ajax({
                    url: "/api/last-member",
                    method: "GET",
                    type: "GET",
                    success: function(response) {
                        data = response.data;

                        if (response.status == "success") {
                            $("#image-preview").attr("src", data.image)

                            $("#info-name").text(data.name)
                            $("#info-membership").text(data.membership)
                            $("#info-expired").text(data.expired_at)
                            if (data.status == 0) {
                                $("#info-status").empty().append(`<div class="badge bg-danger rounded-0 fs-14px">Inactive</div>`)
                            } else {
                                $("#info-status").empty().append(`<div class="badge bg-success rounded-0 fs-14px">Actuve</div>`)
                            }
                        } else {
                            $("#image-preview").attr("src", data.image)
                            $("#info-name").text("-")
                            $("#info-membership").text("-")
                            $("#info-expired").text("-")
                            $("#info-status").text("-")
                        }
                    },
                    error: function(response) {
                        $("#image-preview").attr("src", data.image)
                        $("#info-name").text("-")
                        $("#info-membership").text("-")
                        $("#info-expired").text("-")
                        $("#info-status").text("-")
                    }
                });
            }

            setInterval(function() {
                getLastMember()
            }, 1500)
        })
    </script>
</body>

</html>
