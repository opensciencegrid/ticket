#!/bin/bash

echo "existing branches"
git branch -v

echo "software version says"
grep ">version" ../app/config.php | grep version

echo -n "enter new branch name> "
read -e NAME

git branch -f $NAME
git push origin $NAME

#update latest tag - only do this on production
#git tag -f -a latest

