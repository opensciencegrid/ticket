date=`date +%Y%m%d.%H`_$RANDOM

trunk='https://osg-svn.rtinfo.indiana.edu/goc-internal/footprint/trunk/'
SVN_BRANCHES=https://osg-svn.rtinfo.indiana.edu/goc-internal/footprint/branches
echo "-------------------------------------------------------"
echo "Existing branches"
svn --non-interactive --trust-server-cert list $SVN_BRANCHES
echo "-------------------------------------------------------"
echo -n "Please name your new branch> "
read -e BRANCH

svn rm -m "removing previous (if necessary)" ${SVN_BRANCHES}/${BRANCH}
svn cp -m "creating new branch via branch script" $trunk ${SVN_BRANCHES}/${BRANCH}

