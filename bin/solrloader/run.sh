#!/bin/bash

URL=http://localhost:8983/solr/update

php load_recent.php
count=$?
if [ $count -gt 0 ] ; then
    for f in post/*.xml; do
        echo Posting file $f to $URL
        response=$(curl --write-out %{http_code} --silent --data-binary @$f  -H 'Content-type:application/xml' --output /dev/null $URL)
        if [ $response -eq 200 ]; then
            mv $f posted
        else
            echo "failed with code: $response"
        fi
    done

    #send the commit command to make sure all the changes are flushed and visible
    #curl $URL --data-binary '<commit softCommit=true/>' -H 'Content-type:application/xml'
    echo "comminting everything now"
    curl "$URL?softCommit=true"
    echo
else
    echo "no ticket updated since last run"
fi
