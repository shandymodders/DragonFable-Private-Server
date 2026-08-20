(function () {
    "use strict";

    function byId(id) {
        return document.getElementById(id);
    }

    function setStatus(text, isError) {
        const status = byId("aeu-status");
        if (!status) return;
        status.textContent = text || "";
        status.style.color = isError ? "#ffb0a8" : "#d8c6a4";
    }

    function setButtonEnabled(enabled) {
        const button = byId("aeu-open-button");
        if (!button) return;
        button.disabled = !enabled;
        button.style.opacity = enabled ? "1" : "0.55";
        button.style.cursor = enabled ? "pointer" : "not-allowed";
    }

    function setPlayersButtonEnabled(enabled) {
        const button = byId("players-visibility-button");
        if (!button) return;
        button.disabled = !enabled;
        button.style.opacity = enabled ? "1" : "0.55";
        button.style.cursor = enabled ? "pointer" : "not-allowed";
    }

    function syncPlayersButton(hidden) {
        const button = byId("players-visibility-button");
        if (!button) return;
        button.textContent = hidden ? "Show All Players" : "Hide All Players";
        button.setAttribute("aria-pressed", hidden ? "true" : "false");
    }

    window.openAEUTrainer = function () {
        const player = byId("FFable");

        if (!player) {
            setStatus("Ruffle player was not found.", true);
            return;
        }

        if (typeof player.AEU_Load_From_Web !== "function") {
            setStatus("The AEU callback is not ready. Refresh the page with cache disabled.", true);
            console.error("AEU_Load_From_Web callback is not available on #FFable", player);
            return;
        }

        try {
            const result = player.AEU_Load_From_Web();
            setStatus("AEU load command sent. Check Network for aeu.swf.", false);
            console.log("AEU_Load_From_Web result:", result);
        } catch (error) {
            console.error("Failed to invoke AEU callback:", error);
            setStatus("Failed to open AEU. Check the browser Console.", true);
        }
    };

    window.toggleAllPlayers = function () {
        const multiplayer = window.dfpsMultiplayer;
        if (!multiplayer || typeof multiplayer.setPlayersHidden !== "function") {
            setStatus("The multiplayer controls are not ready. Refresh with cache disabled.", true);
            return;
        }

        const hidden = multiplayer.setPlayersHidden(!multiplayer.playersHidden);
        syncPlayersButton(hidden);
    };

    function init() {
        setButtonEnabled(false);
        setPlayersButtonEnabled(false);
        syncPlayersButton(false);
        setStatus("Log in to DragonFable to enable the AEU button.", false);

        // The private-server game already dispatches this event after a successful login.
        if (
            window.hiperesp &&
            window.hiperesp.dfps &&
            typeof window.hiperesp.dfps.addEventListener === "function"
        ) {
            window.hiperesp.dfps.addEventListener("logged", function () {
                setButtonEnabled(true);
                setPlayersButtonEnabled(true);
                syncPlayersButton(false);
                setStatus("Login successful. AEU Trainer is ready to open.", false);
            });
        } else {
            // Fallback: leave the button usable after the page loads.
            // This is only used if game.js is modified/removed later.
            setButtonEnabled(true);
            setPlayersButtonEnabled(true);
            setStatus("AEU Trainer is ready. Click it after logging in.", false);
        }
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }
})();
