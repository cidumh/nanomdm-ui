function FindProxyForURL(url, host) {
	if (shExpMatch(host, "iprofiles.apple.com")) {
		return "PROXY 127.0.0.1:1234"
	}
	return "DIRECT"
}
