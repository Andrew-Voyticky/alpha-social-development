# Installation (dev env)

Download and install [VirtualBox](https://www.virtualbox.org/wiki/Downloads)

Download and Install [Vagrant](https://www.vagrantup.com/downloads.html)

Clone repository (if didn't before)
```bash
git clone --recursive https://github.com/alpha-social-club/alpha-social-development
```
Go to project folder
```bash
cd alpha-social-development
```

Run vagrant
```bash
vagrant up
```

Add to your hosts file a record:
```nano
 192.168.33.10 alpha-social.dev
```

##System Requirements

1. Reasonably powerful x86 hardware. Any recent Intel or AMD processor should do. 

2. Memory. You will need at least 512 MB of RAM (but probably more, and the more the better).

3. Hard disk space. By default project enviropment size grows dynamically so we recommend that you reserve minimum available 1GB for it. 

4. If you need to change virtual hardware configuration values please check this [page]( https://docs.vagrantup.com/v2/virtualbox/configuration.html)

##Need Help?

Let us have it! Don't hesitate to open a new issue on GitHub if you run into any trouble or have any tips that we need to know. Our email, as always: sassyhelp@alphadirectorate.com.
