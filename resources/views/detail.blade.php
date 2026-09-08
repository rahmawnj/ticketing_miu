<!DOCTYPE html>

<html>

<head>
    <title>miu ticketing system</title>
</head>

<body>

    <div id="bodyData">
        <h1 style="font-size: 75px;margin-top:0px;margin-bottom:0px"> SELAMAT DATANG</h1>
        <center>
            <!-- <img src="{{ asset('no-image') }}" width="480" width="480" alt="The Logo" class="brand-image" style="opacity: .8;text-align:center;margin-top:50px"> -->
        </center>
    </div>

</body>

</html>

<style>
    h1 {
        text-align: center;
    }
</style>
<script src="https://code.jquery.com/jquery-3.6.4.min.js" integrity="sha256-oP6HI9z1XaZNBrJURtCoUT5SUnxFr8s3BzRl+cbzUq8=" crossorigin="anonymous"></script>

<script type="text/javascript">
    $(document).ready(function() {

        let gate = "{{ request('gate') }}";

        setInterval(function() {
            $.ajax({
                type: 'GET',
                url: '/api/ticket/group-last',
                dataType: 'json',
                data: {
                    gate: gate
                },
                success: function(data) {
                    const now = new Date();
                    const update = new Date(data.updated_at)
                    const between = now - update
                    var bodyData = '';
                    $("#bodyData").html(bodyData);
                    if (between < 60000) {
                        bodyData = `
                    <center>
                    <img src="{{ asset('img/no-image.jpg') }}"width="300" width="300" alt="The Logo" class="brand-image" style="opacity: .8;text-align:center;margin-top:50px">
                    </center>
                    <h1>COUNTING TICKET DISPENSER ` + gate + `<h1>
                        </br>

                        <h1 style="font-size: 100px;">${data.amount-data.amount_scanned}</h1>

                    <h1>${data.time}</h1>
                    `
                        $("#bodyData").html(bodyData);
                    } else {
                        bodyData = `<br><br><br><h1 style="font-size: 75px;margin-top:0px;margin-bottom:0px"> SELAMAT DATANG</h1>
                    <center>
                    <img src="{{ asset('img/no-image.jpg') }}"width="480" width="480" alt="The Logo" class="brand-image" style="opacity: .8;text-align:center;margin-top:50px">
                    </center>
                    `
                        $("#bodyData").html(bodyData);
                    }
                },
                error: function() {
                    console.log(data);
                }
            });
        }, 2000);
    });
</script>

