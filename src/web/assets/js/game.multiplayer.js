window.hiperesp.dfps.addEventListener("load", function() {
    hiperesp.dfps.modules.Multiplayer = class Multiplayer {
        constructor(serverLocation) {
            this.serverLocation = serverLocation.replace(/\/$/, "");
            this.user = null;
            this.map = "";
            this.eventSource = null;
            this.lastSentAt = 0;
            this.lastStateKey = "";
            this.activeRemoteIds = new Set();
            this.appearance = new Map();
            this.appearanceRefreshMs = 3000;
            this.appearanceApplyRetryMs = 750;
            this.appearanceReadyCheckMs = 2000;
            this.streamSerial = 0;

            hiperesp.dfps.addEventListener("logged", user => {
                this.user = {
                    id: user.UserID,
                    token: user.strToken,
                    name: user.customParam_username,
                };
            });

            hiperesp.dfps.addEventListener("worldState", state => {
                this.publish(state || {});
            });

            window.addEventListener("pagehide", () => this.leave());
            window.addEventListener("beforeunload", () => this.leave());
        }

        getFlash() {
            return document.getElementById("FFable");
        }

        publish(state) {
            if(!this.user?.token || !state.map) return;

            const normalized = {
                map: String(state.map),
                charId: Number(state.charId || 0),
                classId: Number(state.classId || 0),
                classFile: String(state.classFile || ""),
                moving: Number(state.moving || 0) === 1 || state.moving === true,
                x: Number(state.x),
                y: Number(state.y),
                scaleX: Number(state.scaleX || 100),
                scaleY: Number(state.scaleY || 100),
                dir: String(state.dir || ""),
                frame: Number(state.frame || 1),
                name: String(state.name || ""),
            };

            if(!Number.isInteger(normalized.charId) || normalized.charId <= 0) return;
            if(!Number.isFinite(normalized.x) || !Number.isFinite(normalized.y)) return;

            if(this.map !== normalized.map) {
                this.map = normalized.map;
                this.activeRemoteIds.clear();
                this.appearance.clear();
                this.clearInGame();
                this.openStream();
            }

            const now = performance.now();
            const stateKey = JSON.stringify(normalized);
            if(stateKey === this.lastStateKey && (now - this.lastSentAt) < 800) return;
            if((now - this.lastSentAt) < 90) return;

            this.lastSentAt = now;
            this.lastStateKey = stateKey;

            fetch(this.serverLocation + "/world/update", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                keepalive: true,
                body: JSON.stringify({ token: this.user.token, ...normalized }),
            }).catch(error => console.debug("World update failed", error));
        }

        openStream() {
            if(this.eventSource) {
                this.eventSource.close();
                this.eventSource = null;
            }
            if(!this.user?.token || !this.map) return;

            const query = new URLSearchParams({
                token: this.user.token,
                map: this.map,
            });

            const streamMap = this.map;
            const streamSerial = ++this.streamSerial;
            const source = new EventSource(this.serverLocation + "/world/stream?" + query.toString());
            this.eventSource = source;

            source.onmessage = event => {
                if(this.eventSource !== source || this.streamSerial !== streamSerial || this.map !== streamMap) return;
                try {
                    const players = JSON.parse(event.data);
                    if(Array.isArray(players)) this.renderInGame(players, streamMap);
                } catch(error) {
                    console.error("Invalid multiplayer payload", error);
                }
            };

            source.onerror = () => {
                if(this.eventSource === source) {
                    console.debug("Multiplayer stream disconnected; EventSource will retry automatically.");
                }
            };
        }

        renderInGame(players, streamMap = this.map) {
            if(streamMap !== this.map) return;
            const flash = this.getFlash();
            if(!flash || typeof flash.DFPS_RemotePlayersBegin !== "function" ||
               typeof flash.DFPS_RemotePlayer !== "function" ||
               typeof flash.DFPS_RemotePlayersEnd !== "function") {
                return;
            }

            const active = new Set();

            try {
                flash.DFPS_RemotePlayersBegin();

                for(const player of players) {
                    const id = String(player.id ?? "");
                    const charId = Number(player.charId ?? 0);
                    const x = Number(player.x);
                    const y = Number(player.y);
                    if(!id || !Number.isInteger(charId) || charId <= 0 ||
                       !Number.isFinite(x) || !Number.isFinite(y)) {
                        continue;
                    }

                    if(String(player.map ?? streamMap) !== this.map) continue;
                    active.add(id);

                    const runtimeClassId = Number(player.classId ?? 0);
                    const runtimeClassFile = String(player.classFile ?? "");
                    const runtimeClassKey = `${runtimeClassId}|${runtimeClassFile}`;

                    flash.DFPS_RemotePlayer(
                        id,
                        String(player.name ?? ""),
                        String(player.username ?? ""),
                        charId,
                        x,
                        y,
                        Number(player.scaleX ?? 100),
                        Number(player.scaleY ?? 100),
                        String(player.dir ?? ""),
                        Number(player.frame ?? 1),
                        runtimeClassId,
                        runtimeClassFile,
                        player.moving ? 1 : 0
                    );

                    let appearanceState = this.appearance.get(id);
                    const runtimeChanged = Boolean(appearanceState && appearanceState.runtimeClassKey !== runtimeClassKey);
                    if(appearanceState) {
                        appearanceState.runtimeClassKey = runtimeClassKey;
                        if(runtimeChanged) {
                            appearanceState.appliedVersion = "";
                            appearanceState.lastApplyAt = 0;
                        }
                    }

                    this.ensureAppearance(id, charId);
                    appearanceState = this.appearance.get(id);
                    if(appearanceState && appearanceState.runtimeClassKey === undefined) {
                        appearanceState.runtimeClassKey = runtimeClassKey;
                    }
                    this.applyCachedAppearance(id, charId, runtimeChanged);
                }

                flash.DFPS_RemotePlayersEnd();
            } catch(error) {
                console.debug("DragonFable multiplayer callbacks are not ready", error);
            }

            this.activeRemoteIds = active;

            for(const id of this.appearance.keys()) {
                if(!active.has(id)) this.appearance.delete(id);
            }
        }

        applyCachedAppearance(remoteId, charId, force = false) {
            const cached = this.appearance.get(remoteId);
            if(!cached || cached.charId !== charId || !cached.version || !cached.xml) return;

            const effectiveVersion = cached.version + "|runtimeClass=" + String(cached.runtimeClassKey || "");
            const now = Date.now();
            const retryWindow = cached.appliedVersion === effectiveVersion
                ? this.appearanceReadyCheckMs
                : this.appearanceApplyRetryMs;
            if(!force && (now - (cached.lastApplyAt || 0)) < retryWindow) return;
            cached.lastApplyAt = now;

            const flash = this.getFlash();
            if(!flash || typeof flash.DFPS_RemoteAppearance !== "function") return;

            try {
                const status = String(flash.DFPS_RemoteAppearance(remoteId, charId, effectiveVersion, cached.xml) ?? "");
                cached.lastStatus = status;
                if(status === "ready") {
                    cached.appliedVersion = effectiveVersion;
                } else {
                    // The SWF may have destroyed the remote avatar because its
                    // map/avatar parent was replaced. Clearing appliedVersion
                    // lets the cached XML rebuild the avatar without refetching.
                    cached.appliedVersion = "";
                }
                // loading/deferred/failed are intentionally not marked applied.
                // The same cached XML can therefore be retried after login/map load
                // without another HTTP request or avatar rebuild loop.
            } catch(error) {
                console.debug("Remote appearance callback is not ready", error);
            }
        }

        ensureAppearance(remoteId, charId) {
            const now = Date.now();
            const requestMap = this.map;
            const cached = this.appearance.get(remoteId);

            if(cached?.pending) return;
            if(cached && cached.charId === charId && (now - cached.checkedAt) < this.appearanceRefreshMs) {
                return;
            }

            const next = cached || {
                version: "",
                xml: "",
                appliedVersion: "",
                lastApplyAt: 0,
                lastStatus: "",
                runtimeClassKey: "",
            };
            next.charId = charId;
            next.pending = true;
            next.checkedAt = now;
            this.appearance.set(remoteId, next);

            const query = new URLSearchParams({
                token: this.user.token,
                charId: String(charId),
            });

            fetch(this.serverLocation + "/world/appearance?" + query.toString(), {
                cache: "no-store",
            })
                .then(response => {
                    if(!response.ok) throw new Error("Appearance HTTP " + response.status);
                    return response.json();
                })
                .then(data => {
                    const current = this.appearance.get(remoteId);
                    if(!current || current.charId !== charId) return;

                    current.pending = false;
                    current.checkedAt = Date.now();

                    if(this.map !== requestMap) return;
                    if(!this.activeRemoteIds.has(remoteId)) return;
                    if(!data?.xml || !data?.version) return;

                    const newVersion = String(data.version);
                    const newXml = String(data.xml);
                    const changed = current.version !== newVersion || current.xml !== newXml;
                    current.version = newVersion;
                    current.xml = newXml;

                    if(changed) {
                        current.appliedVersion = "";
                        current.lastApplyAt = 0;
                        current.lastStatus = "";
                    }

                    this.applyCachedAppearance(remoteId, charId, true);
                })
                .catch(error => {
                    const current = this.appearance.get(remoteId);
                    if(current) {
                        current.pending = false;
                        current.checkedAt = Date.now();
                    }
                    console.debug("Remote appearance load failed", error);
                });
        }

        clearInGame() {
            const flash = this.getFlash();
            try {
                if(flash && typeof flash.DFPS_RemotePlayersClear === "function") {
                    flash.DFPS_RemotePlayersClear();
                }
            } catch(error) {
                console.debug("Remote clear callback is not ready", error);
            }
        }

        leave() {
            if(!this.user?.token) return;

            if(this.eventSource) {
                this.eventSource.close();
                this.eventSource = null;
            }

            this.clearInGame();
            this.activeRemoteIds.clear();
            this.appearance.clear();

            const body = JSON.stringify({ token: this.user.token });
            if(navigator.sendBeacon) {
                navigator.sendBeacon(
                    this.serverLocation + "/world/leave",
                    new Blob([body], { type: "application/json" })
                );
                return;
            }

            fetch(this.serverLocation + "/world/leave", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                keepalive: true,
                body,
            }).catch(() => {});
        }
    };

    if(window.serverLocation) {
        window.dfpsMultiplayer = new hiperesp.dfps.modules.Multiplayer(window.serverLocation);
    }
});
