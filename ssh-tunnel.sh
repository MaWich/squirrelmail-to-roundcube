#!/bin/bash

ssh -N gcb-shmail.bln.de.clara.net -L 13306:localhost:3306 &
ssh -N cf1-shmail.prod.shared.mgt.de.clara.net -L 23306:localhost:3306 &
