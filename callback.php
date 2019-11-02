<head>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
</head>
We are working on it...
<script>
    var hash = window.location.hash.substr(1);

    var result = hash.split('&').reduce(function (result, item) {
        var parts = item.split('=');
        result[parts[0]] = parts[1];
        return result;
    }, {});

    var access_token = result["access_token"];
    var global_var = null;

    // Get the json data file from server
    $.getJSON("banned_data.json", function(data) {
        //console.log(data);

        let album_ids = "";

        $.each(data, function(key, value) {
            //console.log(key);

            album_ids += key + "%2C";

            /* Gets every song in an album
            $.each(value['uri'], function (_key, _value)
            {
                console.log(_key, _value);
            });
            */
        });

        /* remove last comma */
        album_ids = album_ids.substring(0, album_ids.length - 3);

        /* Remove albums request */
        $.ajax({
            url: "https://api.spotify.com/v1/me/albums?ids=" + album_ids + "",
            type: "DELETE",
            headers: {
                'Authorization': 'Bearer ' + access_token
            },
            success: function(response) {
                console.log(response);
                console.log("success: " + response.responseText);
            },
            error: function(response) {
                console.log("error: " + response.responseText);
            }
        });


    });
    

</script>