#!/bin/bash

# forcando o uso do JDK6
JAVA_HOME=/usr/bin/java

JBPATH=$HOME/www/palestra/JavaBridge
JBPORT=9080
JBLOGLEVEL=5
JBLOGFILE=$JBPATH/JavaBridge.log
JBCALL="$JAVA_HOME -Djava.library.path=$JBPATH/lib/ -Djava.class.path=$JBPATH/lib/JavaBridge.jar -Djava.awt.headless=true -Dphp.java.bridge.base=$JBPATH/lib/ php.java.bridge.Standalone SERVLET:$JBPORT $JBLOGLEVEL $JBLOGFILE"

if [ "$USER" = "root" ]; then
	echo "Nao pode ser executado como root"
	exit 0
fi

/bin/rm -f $JBPATH/swap_* &>/dev/null
if [ "$1" = "start" ]; then
	PID=`/bin/ps -o pid,command -u $USER | /bin/grep "/bin/java " | /bin/grep "JavaBridge.jar" | /bin/grep -v "grep" | /usr/bin/cut -d" " -f1`
	if [ "$PID" != "" ]; then
		echo JavaBridge ja esta rodando no PID $PID
	else
		echo Iniciando JavaBridge porta $JBPORT...
		$JBCALL 1>$JBLOGFILE 2>&1 &
		echo "$JBCALL 1>$JBLOGFILE 2>&1 &"
		PID=`/bin/ps -o pid,command -u $USER | /bin/grep "/bin/java " | /bin/grep "JavaBridge.jar" | /bin/grep -v "grep" | /usr/bin/cut -d" " -f1`
		echo JavaBridge rodando na porta $JBPORT
	fi
fi

if [ "$1" = "stop" ]; then
	echo Parando JavaBridge...
	/bin/kill -9 `/bin/ps -o pid,command -u $USER | /bin/grep "/bin/java " | /bin/grep "JavaBridge.jar" | /bin/grep -v "grep" | /usr/bin/cut -d" " -f2` 2> /dev/null
fi
