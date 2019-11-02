<?

?>

<head>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
</head>

<h1>Hello!</h1>

<input type="button" value="Click me!" onclick="authorize()"/>


<script>
    console.log("test");
    //test1234

    function authorize()
    {

        window.location = 'https://accounts.spotify.com/authorize?client_id=e746c15c3dc4456db8a6fa254b8c6bf5&redirect_uri=http:%2F%2Fapoiat.com%2Fjustify%2Fcallback.php&scope=user-library-modify&response_type=token&state=123';
        /*
        let client_id = 'e746c15c3dc4456db8a6fa254b8c6bf5';

        let url = 'https://accounts.spotify.com/authorize?client_id=' + client_id  + '&response_type=code&redirect_uri=https%3A%2F%2Fexample.com%2Fcallback&scope=user-read-private%20user-read-email';

        $.ajax({
            url: client_id,
            type: "GET",
            dataType: "jsonp",
            success: function (data) {
                console.log(data);
            }
        });
        */
    }

</script>