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

    // Create an array pool with all songs
    var tracksPool = [];
    var albumsPool = [];
    var MAX_TRACKS_TO_REMOVE_PER_REQUEST = 50;
    var MAX_ALBUMS_TO_REMOVE_PER_REQUEST = 50;

    var REMOVE_TRACKS_FUNCTION = false;
    var REMOVE_ALBUMS_FUNCTION = true;

    // Get the json data file from server
    $.getJSON("banned_data.json", function(data) {


        /* First collect all tracks from our data file */
        $.each(data, function(key, value) {

            /* Create the tracks pool by parsing every track */
            $.each(value.tracks, function(subkey, subvalue)
            {
                tracksPool.push(subvalue);                
            });

            /* Create the albums pool by parsing every album */
            $.each(value.albums, function(subkey, subvalue)
            {
                /* check for double entries */
                let album_already_exists = false;

                $.each(albumsPool, function() {
                    if (this == subvalue) album_already_exists = true;
                });

                /* If no double entries are detected add to the pool */
                if (!album_already_exists)
                {
                    albumsPool.push(subvalue);
                }
            });

        });

        /*
        *  1. Request all user playlists 
        *  2. Check every song for a match in our tracksPool
        *  3. Send a request for every playlist to remove the tracks matched
        */
        if (REMOVE_TRACKS_FUNCTION)
        {

            $.ajax({
                url: "https://api.spotify.com/v1/me/playlists",
                type: "GET",
                headers: {
                    'Authorization': 'Bearer ' + access_token
                },
                success: function(playlistsResponse) {

                    /* Parse every returned playlist */
                    $.each(playlistsResponse.items, function()
                    {
                        let current_playlist = this;

                        /* Make subsequent calls to retrieve the tracks of every playlist tracks url */
                        $.ajax({
                            url: this.tracks.href,
                            type: "GET",
                            headers: {
                                'Authorization': 'Bearer ' + access_token
                            },
                            success: function(tracksResponse) {
                                
                                let tracks_to_remove = [];
                                let remove_requests = [];

                                $.each(tracksResponse.items, function()
                                {
                                    let tmpTrack = this.track.uri;

                                    $.each(tracksPool, function()
                                    {
                                        if (this == tmpTrack)
                                        {
                                            /* check if track already exists in tracks_to_remove_pool */
                                            let track_already_exists = false;

                                            $.each(tracks_to_remove, function () {
                                                if (this == tmpTrack) track_already_exists = true;
                                            });

                                            /* If not throw it in */
                                            if (!track_already_exists)
                                            {
                                                tracks_to_remove.push(tmpTrack);
                                            }
                                        }
                                    });
                                

                                });

                                /* If any matches found remove these tracks from user's playlist */
                                if (tracks_to_remove.length > 0)
                                {
                                    let init_remove_request = {};
                                    init_remove_request.tracks = [];

                                    remove_requests.push(init_remove_request);  

                                    console.log("Found matches on : " + current_playlist.id);
                                    /* check if the tracks exceed the maximum tracks limit per request */
                                    if (tracks_to_remove.length > MAX_TRACKS_TO_REMOVE_PER_REQUEST)
                                    {
                                        /* if so break the requests into segments */
                                        let current_request_num = 0;
                                        let current_songs_parsed = 0;

                                        $.each(tracks_to_remove, function()
                                        {
                                            if (current_songs_parsed == MAX_TRACKS_TO_REMOVE_PER_REQUEST)
                                            {
                                                current_songs_parsed = 0;
                                                current_request_num += 1;

                                                let new_remove_request = {};
                                                    remove_request.tracks = [];

                                                remove_requests.push(new_remove_request);
                                            }

                                            remove_requests[current_request_num].tracks.push(this);
                                            current_songs_parsed++;
                                            console.log("pushing: " + this);
                                        });
                                    } 
                                    /* If tracks do not exceed 50 total matched from playlist. */
                                    else 
                                    {
                                        console.log('matches no more than 50');
                                        remove_requests[0].tracks = tracks_to_remove;
                                    }
                                    
                                    //console.log(remove_requests);
                                }
                                else
                                {
                                    console.log("no matches found for playlist " + current_playlist.id);
                                }

                                /* Check for any remove requests and proccess them */
                                $.each(remove_requests, function()
                                {

                                    /* convert track data to spotify acceptable format before submitting */
                                    let tracks_data = {};
                                        tracks_data['tracks'] = [];

                                    $.each (this.tracks, function() {
                                        let tmpTrackObj = {};
                                            tmpTrackObj['uri'] = this;
                                        tracks_data['tracks'].push (tmpTrackObj);
                                    });
                                    
                                    console.log("New formatted data for playlist " + current_playlist.id);
                                    console.log(addslashes(JSON.stringify(tracks_data)));
                                    
                                    $.ajax({
                                        url: "https://api.spotify.com/v1/playlists/" + current_playlist.id + "/tracks",
                                        type: "DELETE",
                                        data: JSON.stringify(tracks_data),
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
                                    

                                    /* */
                                });
                                console.log("All remove requests for this playlist:");
                                console.log(remove_requests);

                            },
                            error: function(response) {
                                console.log("error: " + response.responseText);
                            }
                        });

                    });
                },

                error: function(response) {
                    console.log("error: " + response.responseText);
                }
            });
        }
    
        /*
        *  1. Parse the albumsPool array and create request blocks if the number of entries exceed the total defined in MAX_ALBUMS_TO_REMOVE_PER_REQUEST
        *  2. Convert the request block elements to an appropriate albums string
        *  3. Proccess all the requests
        */
        if (REMOVE_ALBUMS_FUNCTION)
        {
            /* First, if albums to remove exceed 50, break them into requests */
            let remove_requests = [];
            let init_request = [];

            remove_requests.push(init_request);

            if (albumsPool > MAX_ALBUMS_TO_REMOVE_PER_REQUEST)
            {
                let current_albums_parsed = 0;
                let current_request_num = 0;

                $.each(albumsPool, function()
                {
                    if (current_albums_parsed == MAX_ALBUMS_TO_REMOVE_PER_REQUEST)
                    {
                        current_albums_parsed = 0;
                        current_request_num++;

                        let new_remove_request = [];
                        
                        remove_requests.push(new_remove_request);
                    }

                    remove_requests[current_request_num].push(this);
                });

            } else {
                remove_requests[0] = albumsPool;
            }

            /* After sorting / breaking the albums per maximum request, proccess every album request */
            $.each(remove_requests, function() 
            {
                /* Create the string including all the albums with proper format */
                let albums_string = "";
                $.each (this, function() {
                    albums_string += this.split(':')[2] + ",";
                });

                console.log(albums_string);
                /* Perform the request with max 50 albums per */
                $.ajax({
                    url: "https://api.spotify.com/v1/me/albums?ids=" + albums_string + "",
                    type: "DELETE",
                    headers: {
                        'Authorization': 'Bearer ' + access_token
                    },
                    success: function(response) {
                        console.log(response);
                        console.log("success: " + response);
                    },
                    error: function(response) {
                        console.log("error: " + response.responseText);
                    }
                });

            });


        }


    });

        /* remove last comma */
        //album_ids = album_ids.substring(0, album_ids.length - 3);


        /* Remove albums request */
        /*
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
        
        */






        function addslashes( str ) {
            return (str + '').replace(/[\\"']/g, '\\$&').replace(/\u0000/g, '\\0');
        }
</script>