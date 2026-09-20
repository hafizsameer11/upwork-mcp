"use client";

import { Suspense, useEffect, useState } from "react";
import { useSearchParams } from "next/navigation";
import { api } from "@/lib/api";

function AccountsInner() {
  const search = useSearchParams();
  const [rows, setRows] = useState<any[]>([]);
  const [label, setLabel] = useState("");
  const [token, setToken] = useState("");
  const [busyId, setBusyId] = useState<number | null>(null);
  const [showManual, setShowManual] = useState(false);
  const [message, setMessage] = useState<{ type: "ok" | "err"; text: string } | null>(null);

  function load() {
    api<any[]>("/accounts").then(setRows).catch(console.error);
  }

  useEffect(() => {
    load();
  }, []);

  useEffect(() => {
    const oauth = search.get("oauth");
    if (oauth === "connected") {
      setMessage({ type: "ok", text: "Upwork account connected. Portfolio/messages sync queued." });
      load();
      window.history.replaceState({}, "", "/accounts");
    } else if (oauth === "error") {
      setMessage({ type: "err", text: search.get("message") || "OAuth failed." });
      window.history.replaceState({}, "", "/accounts");
    }
  }, [search]);

  async function create() {
    if (!label.trim()) return;
    await api("/accounts", { method: "POST", body: JSON.stringify({ label, is_default: rows.length === 0 }) });
    setLabel("");
    load();
  }

  async function connectOAuth(id: number) {
    setBusyId(id);
    setMessage(null);
    try {
      const res = await api<{ authorize_url: string }>(`/accounts/${id}/oauth/start`, { method: "POST" });
      window.location.href = res.authorize_url;
    } catch (e) {
      setMessage({ type: "err", text: e instanceof Error ? e.message : "Could not start OAuth" });
      setBusyId(null);
    }
  }

  async function connectManual(id: number) {
    if (!token.trim()) {
      setMessage({ type: "err", text: "Paste an access token first." });
      return;
    }
    setBusyId(id);
    try {
      await api(`/accounts/${id}/connect`, {
        method: "POST",
        body: JSON.stringify({ access_token: token }),
      });
      setToken("");
      setMessage({ type: "ok", text: "Token saved." });
      load();
    } catch (e) {
      setMessage({ type: "err", text: e instanceof Error ? e.message : "Connect failed" });
    } finally {
      setBusyId(null);
    }
  }

  return (
    <div className="space-y-6 max-w-3xl">
      <header>
        <h1 className="text-3xl font-semibold">Upwork Accounts</h1>
        <p className="text-zinc-400 mt-1">
          Multi-account / agency. Click <span className="text-zinc-200">Connect with Upwork</span> to authorize via
          browser OAuth (MCP).
        </p>
      </header>

      {message && (
        <div
          className={`rounded-xl border px-4 py-3 text-sm ${
            message.type === "ok"
              ? "border-emerald-500/30 bg-emerald-500/10 text-emerald-200"
              : "border-red-500/30 bg-red-500/10 text-red-200"
          }`}
        >
          {message.text}
        </div>
      )}

      <div className="border border-zinc-800 rounded-xl p-5 space-y-3">
        <input
          className="w-full bg-zinc-950 border border-zinc-700 rounded-lg px-3 py-2"
          placeholder="Account label (e.g. Sohaib, Agency)"
          value={label}
          onChange={(e) => setLabel(e.target.value)}
        />
        <button onClick={create} className="rounded-lg bg-emerald-500 text-zinc-950 px-4 py-2 font-medium">
          Add account
        </button>
      </div>

      <div className="space-y-3">
        {rows.map((a) => (
          <div key={a.id} className="border border-zinc-800 rounded-xl p-4 flex flex-wrap gap-3 justify-between items-center">
            <div>
              <div className="font-medium">{a.label}</div>
              <div className="text-xs text-zinc-500 mt-0.5">
                {a.is_active ? "active" : "inactive"} · {a.is_connected ? "connected" : "needs login"}
                {a.is_default ? " · default" : ""}
              </div>
            </div>
            <div className="flex flex-wrap gap-2">
              <button
                onClick={() => connectOAuth(a.id)}
                disabled={busyId === a.id}
                className="text-sm rounded-lg bg-emerald-500 text-zinc-950 px-3 py-1.5 font-medium disabled:opacity-50"
              >
                {busyId === a.id ? "Redirecting…" : a.is_connected ? "Reconnect with Upwork" : "Connect with Upwork"}
              </button>
              <button
                onClick={() =>
                  api(`/accounts/${a.id}/sync`, { method: "POST" }).then(() =>
                    setMessage({ type: "ok", text: "Sync queued." })
                  )
                }
                className="text-sm rounded-lg border border-zinc-700 px-3 py-1.5"
              >
                Sync
              </button>
            </div>
          </div>
        ))}
        {!rows.length && <p className="text-sm text-zinc-500">No accounts yet — add one above.</p>}
      </div>

      <div className="border border-zinc-800 rounded-xl p-5 space-y-3">
        <button
          type="button"
          onClick={() => setShowManual((v) => !v)}
          className="text-sm text-zinc-400 hover:text-zinc-200"
        >
          {showManual ? "Hide" : "Show"} advanced: paste token manually
        </button>
        {showManual && (
          <>
            <p className="text-xs text-zinc-500">
              Only use if browser OAuth fails. Prefer{" "}
              <strong className="font-medium text-zinc-300">Connect with Upwork</strong>.
            </p>
            <textarea
              className="w-full min-h-24 bg-zinc-950 border border-zinc-700 rounded-lg px-3 py-2 text-sm"
              placeholder="Paste access token"
              value={token}
              onChange={(e) => setToken(e.target.value)}
            />
            {rows.map((a) => (
              <button
                key={a.id}
                onClick={() => connectManual(a.id)}
                disabled={busyId === a.id}
                className="mr-2 text-sm rounded-lg border border-zinc-700 px-3 py-1.5 disabled:opacity-50"
              >
                Save token → {a.label}
              </button>
            ))}
          </>
        )}
      </div>
    </div>
  );
}

export default function AccountsPage() {
  return (
    <Suspense fallback={<p className="text-zinc-500">Loading…</p>}>
      <AccountsInner />
    </Suspense>
  );
}
