#!/usr/bin/env bash
# Turns a fresh Ubuntu 22.04 / 24.04 server into the i-HR "fixed exit IP":
# a Tailscale exit node. HR staff connect to it with the Tailscale app, so all their
# traffic (including i-HR) leaves from this server's static IP, which is what
# IP_ALLOWLIST in production allows.
#
# Run as root on the new server:
#   curl -fsSL https://raw.githubusercontent.com/yazidmuhd/ihr/main/deploy/exit-node/setup.sh | bash   (after this file is pushed)
# or copy the file over and:  sudo bash setup.sh
#
# Optional: TS_AUTHKEY=tskey-auth-... (from the Tailscale admin console) to join without a browser login.
set -euo pipefail

if [ "$(id -u)" -ne 0 ]; then
  echo "Run as root (sudo bash setup.sh)" >&2
  exit 1
fi

HOSTNAME_TS="${TS_HOSTNAME:-ihr-exit}"
WAN_IF="$(ip route show default | awk '/default/ {print $5; exit}')"
export DEBIAN_FRONTEND=noninteractive

echo "==> Updating the system and enabling automatic security updates"
apt-get update -y
apt-get upgrade -y
apt-get install -y curl ufw unattended-upgrades
dpkg-reconfigure -f noninteractive unattended-upgrades

echo "==> Installing Tailscale"
curl -fsSL https://tailscale.com/install.sh | sh

echo "==> Enabling IP forwarding (required for an exit node)"
cat > /etc/sysctl.d/99-tailscale-exit-node.conf <<SYSCTL
net.ipv4.ip_forward = 1
net.ipv6.conf.all.forwarding = 1
SYSCTL
sysctl -p /etc/sysctl.d/99-tailscale-exit-node.conf

echo "==> Firewall: nothing reachable from the internet except SSH and Tailscale"
ufw --force reset
ufw default deny incoming
ufw default allow outgoing
ufw default deny routed
ufw allow OpenSSH
ufw allow 41641/udp                                   # Tailscale direct connections
ufw allow in on tailscale0                            # traffic from HR devices on the tailnet
ufw route allow in on tailscale0 out on "$WAN_IF"     # forward their traffic to the internet
ufw --force enable

echo "==> Joining the tailnet as an exit node ($HOSTNAME_TS)"
if [ -n "${TS_AUTHKEY:-}" ]; then
  tailscale up --advertise-exit-node --hostname="$HOSTNAME_TS" --authkey="$TS_AUTHKEY"
else
  echo "    Open the login link below in your browser to add this server to your tailnet:"
  tailscale up --advertise-exit-node --hostname="$HOSTNAME_TS"
fi

IPV4="$(curl -4 -fsS --max-time 10 https://ifconfig.me || echo 'none')"
IPV6="$(curl -6 -fsS --max-time 10 https://ifconfig.me || echo 'none')"

cat <<DONE

============================================================
 Exit node ready: $HOSTNAME_TS
 Public IPv4: $IPV4
 Public IPv6: $IPV6

 Next steps
 1. Tailscale admin console -> Machines -> $HOSTNAME_TS -> Edit route settings
    -> tick "Use as exit node". Also: ... -> Disable key expiry.
 2. Railway (production, web + worker) -> Variables:
      IP_ALLOWLIST=$IPV4$( [ "$IPV6" != "none" ] && echo ", $IPV6" )
      IP_ALLOWLIST_SCOPE=hr
      CLIENT_IP_HEADER=X-Real-IP
 3. On each HR computer: install Tailscale, sign in, choose
    Exit node -> $HOSTNAME_TS. Then https://ifconfig.me must show $IPV4.
============================================================
DONE
