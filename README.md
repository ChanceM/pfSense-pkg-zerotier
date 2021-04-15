# pfSense-pkg-zerotier
pfSense package to support zerotier.

## Pre-reqs
1. FreeBSD 12.2 Workstation with pkg, git, and gmake

## Build
1. `git clone https://github.com/pfsense/FreeBSD-ports.git`
2. add `ALLOW_UNSUPPORTED_SYSTEM=YES` to /etc/make.conf
3. Copy these files to FreeBSD-ports/net/pfSense-pkg-zerotier
4. Run `make clean ; make package`
5. scp work/pkg/pfSense-pkg-zerotier-0.00.1.txz to pfsense

## Install
1. Run `pkg add http://pkg.freebsd.org/freebsd:12:x86:64/latest/All/zerotier-1.6.4.txz`
2. Run `pkg add pfsense-pkg-zerotier-0.00.1.txz`

## ToDo
- [ ] Re-write controller functionality to match API changes
- [ ] Interface creation
