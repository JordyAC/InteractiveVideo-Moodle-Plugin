define([], function() {
    'use strict';

    var instances = {};

    var VideoAdapter = function(selector) {
        if (instances[selector]) {
            return instances[selector];
        }

        this.events = {};
        this.element = document.querySelector(selector);
        if (!this.element) return;

        instances[selector] = this;

        this.isYouTube = this.element.hasAttribute('data-youtubeid');
        if (this.isYouTube) {
            this.youtubeId = this.element.getAttribute('data-youtubeid');
            this.initYouTube();
        } else {
            this.video = this.element;
            this.bindHtml5Events();
        }
    };

    VideoAdapter.prototype.initYouTube = function() {
        var self = this;
        this.timeupdateInterval = null;
        
        var loadPlayer = function() {
            self.ytPlayer = new YT.Player(self.element.id, {
                height: '100%',
                width: '100%',
                videoId: self.youtubeId,
                playerVars: {
                    'playsinline': 1,
                    'controls': 1,
                    'disablekb': 1,
                    'rel': 0,
                    'modestbranding': 1
                },
                events: {
                    'onReady': function() {
                        self.trigger('loadedmetadata');
                    },
                    'onStateChange': function(event) {
                        if (event.data === YT.PlayerState.PLAYING) {
                            self.trigger('play');
                            if (!self.timeupdateInterval) {
                                self.timeupdateInterval = setInterval(function() {
                                    self.trigger('timeupdate');
                                }, 250);
                            }
                        } else {
                            if (event.data === YT.PlayerState.PAUSED) {
                                self.trigger('pause');
                            } else if (event.data === YT.PlayerState.ENDED) {
                                self.trigger('ended');
                            }
                            if (self.timeupdateInterval) {
                                clearInterval(self.timeupdateInterval);
                                self.timeupdateInterval = null;
                            }
                        }
                    }
                }
            });
        };

        if (typeof YT === 'undefined' || typeof YT.Player === 'undefined') {
            window.onYouTubeIframeAPIReady = function() {
                loadPlayer();
            };
            var tag = document.createElement('script');
            tag.src = "https://www.youtube.com/iframe_api";
            var firstScriptTag = document.getElementsByTagName('script')[0];
            firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
        } else {
            loadPlayer();
        }
    };

    VideoAdapter.prototype.bindHtml5Events = function() {
        var self = this;
        ['timeupdate', 'seeking', 'ended', 'play', 'pause', 'loadedmetadata'].forEach(function(eventName) {
            self.video.addEventListener(eventName, function(e) {
                self.trigger(eventName, e);
            });
        });
    };

    VideoAdapter.prototype.addEventListener = function(eventName, callback) {
        if (!this.events[eventName]) {
            this.events[eventName] = [];
        }
        this.events[eventName].push(callback);
    };

    VideoAdapter.prototype.removeEventListener = function(eventName, callback) {
        if (!this.events[eventName]) return;
        this.events[eventName] = this.events[eventName].filter(function(cb) {
            return cb !== callback;
        });
    };

    VideoAdapter.prototype.trigger = function(eventName, eventObj) {
        if (this.events[eventName]) {
            this.events[eventName].forEach(function(callback) {
                callback(eventObj);
            });
        }
    };

    VideoAdapter.prototype.play = function() {
        if (this.isYouTube && this.ytPlayer && this.ytPlayer.playVideo) {
            this.ytPlayer.playVideo();
        } else if (!this.isYouTube && this.video) {
            this.video.play();
        }
    };

    VideoAdapter.prototype.pause = function() {
        if (this.isYouTube && this.ytPlayer && this.ytPlayer.pauseVideo) {
            this.ytPlayer.pauseVideo();
        } else if (!this.isYouTube && this.video) {
            this.video.pause();
        }
    };

    Object.defineProperty(VideoAdapter.prototype, 'currentTime', {
        get: function() {
            if (this.isYouTube && this.ytPlayer && this.ytPlayer.getCurrentTime) {
                return this.ytPlayer.getCurrentTime();
            } else if (!this.isYouTube && this.video) {
                return this.video.currentTime;
            }
            return 0;
        },
        set: function(val) {
            if (this.isYouTube && this.ytPlayer && this.ytPlayer.seekTo) {
                this.ytPlayer.seekTo(val, true);
            } else if (!this.isYouTube && this.video) {
                this.video.currentTime = val;
            }
        }
    });

    Object.defineProperty(VideoAdapter.prototype, 'duration', {
        get: function() {
            if (this.isYouTube && this.ytPlayer && this.ytPlayer.getDuration) {
                return this.ytPlayer.getDuration();
            } else if (!this.isYouTube && this.video) {
                return this.video.duration;
            }
            return 0;
        }
    });

    return VideoAdapter;
});
