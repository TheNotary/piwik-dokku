# Piwik on Dokku

## Deploy to Dokku

###### Overview
  * 1. Configure the desired env vars in app.json
  * 2. Push to your dokku server
  * 3. Create and link a mysql database
  * 4. Push the app a second time (it will work this time...)
  * 5. Login to the app, and copy/ paste the piwik tracker js snippet and paste into your production machine's html code

#### 1. Configure app.json

At the bottom of this config file, you'll find a listing of env vars and their descriptions

#### 2. Push to your dokku server

Dokku is very easy to setup, first install dokku from [here](http://dokku.viewdocs.io/dokku/), then simply add it as a remote to this repository.  Finally, a push to the master branch will deploy the app.  

```
$ git remote add dokku your-dokku-server.local:/piwik
$ git push dokku master
```

You'll get an error message, that's because you have yet to create the database for it (see step 3).  

#### 3. Create and link a mysql database

You might need to install the dokku mysql plugin, an easy [task](https://github.com/dokku/dokku-mysql).  As for configuring it...

```
$ dokku mysql:create piwik-db
$ dokku mysql:link piwik-db piwik # assumes you named your remote piwik
```

#### 4. Push the app a second time (it will work this time...)

Yeah... push again... the first time was just to cache all the layers and also create the piwik app in the first place.  

```
git push dokku master
```


#### 5. Login to the app...

At this point, everything is deployed, and thanks to some php hackery, when you navigate to the fresh app, it should display the `Setup a Website` page.  The final matter is using piwik to track traffic/ individuals visiting your website.  You'll need to add a website, and copy/paste the tracking js snippet into the production app you'd like monitored.  


## Deploy via Bare Docker Containers

For testing purposes, you have the option to boot a mysql container and then boot this piwik container with a switch to link it to the db container.  Checkout the Makefile to see how to do this with the following commands `make; make boot-containers-dependency; make run`, you'll find ENV variables that must be set at the top of that script.  Dokku is much more sophisticated.  

```
# Required ENVs (put into a vars.sh file and then source it before booting)
export MYSQL_PASSWORD=my-secret-pw
export MYSQL_DATABASE=piwik-db
export MYSQL_CONTAINER_NAME=piwik-db-container
export PIWIK_DATABASE_URL=mysql://root:my-secret-pw@piwik-db-container:3306/piwik-db


# Boot a mysql container creating a fresh db
$ docker run --name piwik-db-container \
  -e MYSQL_ROOT_PASSWORD=my-secret-pw \
  -e MYSQL_DATABASE=piwik-db \
  -d mysql/mysql-server:5.7

# Define the DATABASE_URL env on the piwik container
# eg... DATABASE_URL=mysql://root:my-secret-pw@piwik-db-container:3306/piwik-db

# Boot piwik with a link switch
$ docker run -it \
  -p 3000:3000 \
  -e DATABASE_URL=mysql://root:my-secret-pw@piwik-db-container:3306/piwik-db \
  --link piwik-db-container \
  ${USER}/${DOCKER_IMAGE_NAME} bash
```
