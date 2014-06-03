echo "delete all"
java -Ddata=args -Dcommit=true -jar /usr/local/solr-4.5.0/example/exampledocs/post.jar "<delete><query>id:*</query></delete>"

