DOCKER_IMAGE_NAME=piwik-dokku
MYSQL_CONTAINER_NAME=piwik-dokku-db-containera
DB_PASS=my-secret-pw
DB_NAME=piwik-dokku-db
DATABASE_URL=mysql://root:${DB_PASS}@${MYSQL_CONTAINER_NAME}:3306/${DB_NAME}
ADMIN_LOGIN=admin
ADMIN_EMAIL=a@b.com
ADMIN_PASSWORD=password1


build:
	docker build -t ${USER}/${DOCKER_IMAGE_NAME} .

# If you have to configure volumes, do that from here
# configure:
# Populate the DATABASE_URL from the output of this container
boot-containers-dependency:
	(docker start ${MYSQL_CONTAINER_NAME}) || \
  docker run --name ${MYSQL_CONTAINER_NAME} \
  -e MYSQL_ROOT_PASSWORD=${DB_PASS} \
  -e MYSQL_DATABASE=${DB_NAME} \
  -d mysql/mysql-server:5.7

run:
	(docker start ${DOCKER_IMAGE_NAME}) || \
  docker run \
  -e DATABASE_URL=${DATABASE_URL} \
  -e ADMIN_LOGIN=${ADMIN_LOGIN} \
  -e ADMIN_EMAIL=${ADMIN_EMAIL} \
  -e ADMIN_PASSWORD=${ADMIN_PASSWORD} \
  --link ${MYSQL_CONTAINER_NAME} \
  -p 3000:3000 \
  --name ${DOCKER_IMAGE_NAME} -d ${USER}/${DOCKER_IMAGE_NAME}

console:
	docker run -it \
  -e DATABASE_URL=${DATABASE_URL} \
  -e ADMIN_LOGIN=${ADMIN_LOGIN} \
  -e ADMIN_EMAIL=${ADMIN_EMAIL} \
  -e ADMIN_PASSWORD=${ADMIN_PASSWORD} \
  --link ${MYSQL_CONTAINER_NAME} \
  -p 3000:3000 \
  ${USER}/${DOCKER_IMAGE_NAME} bash

clean:
	docker stop ${DOCKER_IMAGE_NAME}; \
  docker rm ${DOCKER_IMAGE_NAME}; \
  docker stop ${MYSQL_CONTAINER_NAME}; \
  docker rm ${MYSQL_CONTAINER_NAME};

.PHONY: build
