From ubuntu:latest
MAINTAINER Niall Barr <niall.barr@glasgow.ac.uk>

ENV TZ=Europe/London
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

RUN apt-get update
RUN apt-get upgrade
RUN apt-get -y install apt-utils
RUN apt-get -y install ssh
RUN apt-get -y install apache2 
#RUN apt-get -y php7.2 libapache2-mod-php7.2

RUN apt-get -y install python3-pip

RUN pip3 install dill
RUN pip3 install matplotlib
RUN pip3 install pandas
RUN pip3 install gmplot
RUN pip3 install IPython
RUN pip3 install func-timeout
RUN apt-get install libapache2-mod-wsgi-py3

ADD erys.py /var/www/html/erys.py
ADD hello.py /var/www/html/hello.py
ADD 000-default.conf /etc/apache2/sites-enabled/000-default.conf

#Temp - this should be a separate data folder, and owned by the web process
RUN chmod 777 /var/www/html


EXPOSE 80

CMD /usr/sbin/apache2ctl -D FOREGROUND