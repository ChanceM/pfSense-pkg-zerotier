# pfSense-pkg-zerotier
pfSense package to support zerotier.

## Install
1. Build [zerotier](https://github.com/zerotier/ZeroTierOne) dev branch.
2. Copy these files and build on FreeBSD.
3. SCP to pfSense
4. Run `pkg add pfsense-pkg-zerotier-1.0.txz`
5. Run `Service zerotier start`
