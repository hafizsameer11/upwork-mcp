"use client";

import { useEffect, useState } from "react";
import { api } from "@/lib/api";

export default function SettingsPage() {
  const [settings, setSettings] = useState<any>(null);
  const [webhook, setWebhook] = useState("");
  const [msg, setMsg] = useState("");

  useEffect(() => {
    api("/settings").then((s: any) => {
      setSettings(s);
      setWebhook(s.slack?.webhook_url || "");
    });
  }, []);

  async function save() {
    const next = await api("/settings", {
      method: "PUT",
      body: JSON.stringify({
        frequency_minutes: settings.frequency_minutes,
        slack: { ...(settings.slack || {}), webhook_url: webhook },
      }),
    });
    setSettings(next);
    setMsg("Saved");
  }

  if (!settings) return <p className="text-zinc-500">Loading…</p>;

  return (
    <div className="space-y-6 max-w-2xl">
      <header>
        <h1 className="text-3xl font-semibold">Settings</h1>
        <p className="text-zinc-400">Slack alerts only. Polling stays off until Upwork Support clears you.</p>
      </header>

      <div className="border border-zinc-800 rounded-xl p-5 space-y-4">
        <div className="text-sm">
          <div className="text-zinc-500">Polling enabled</div>
          <div className={settings.polling_enabled ? "text-emerald-400" : "text-amber-400"}>
            {settings.polling_enabled ? "YES" : "NO (UPWORK_POLLING_ENABLED=false)"}
          </div>
        </div>
        <div className="text-sm">
          <div className="text-zinc-500">MCP URL</div>
          <div className="font-mono text-xs">{settings.mcp_url}</div>
        </div>
        <div className="text-sm">
          <div className="text-zinc-500">OpenAI</div>
          <div>{settings.openai_configured ? "Configured" : "Missing OPENAI_API_KEY"}</div>
        </div>
        <label className="block text-sm">
          <span className="text-zinc-500">Slack webhook URL</span>
          <input className="mt-1 w-full bg-zinc-950 border border-zinc-700 rounded-lg px-3 py-2" value={webhook} onChange={(e) => setWebhook(e.target.value)} />
        </label>
        <label className="block text-sm">
          <span className="text-zinc-500">Watch frequency (minutes)</span>
          <select
            className="mt-1 w-full bg-zinc-950 border border-zinc-700 rounded-lg px-3 py-2"
            value={settings.frequency_minutes}
            onChange={(e) => setSettings({ ...settings, frequency_minutes: Number(e.target.value) })}
          >
            <option value={15}>15</option>
            <option value={30}>30</option>
            <option value={60}>60</option>
          </select>
        </label>
        <button onClick={save} className="rounded-lg bg-emerald-500 text-zinc-950 px-4 py-2 font-medium">Save</button>
        {msg && <p className="text-sm text-zinc-400">{msg}</p>}
      </div>
    </div>
  );
}
