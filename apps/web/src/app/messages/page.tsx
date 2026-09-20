"use client";

import { useEffect, useState } from "react";
import { api } from "@/lib/api";

export default function MessagesPage() {
  const [rows, setRows] = useState<any[]>([]);
  useEffect(() => {
    api<{ data: any[] }>("/messages").then((r) => setRows(r.data || [])).catch(console.error);
  }, []);

  return (
    <div className="space-y-6">
      <header>
        <h1 className="text-3xl font-semibold">Messages</h1>
        <p className="text-zinc-400">Synced from Upwork MCP — replies, invitations, offers.</p>
      </header>
      <div className="space-y-3">
        {rows.map((m) => (
          <div key={m.id} className="border border-zinc-800 rounded-xl p-4">
            <div className="text-xs text-zinc-500">{m.message_type} · {m.direction} · {m.sent_at}</div>
            <div className="text-sm mt-2 whitespace-pre-wrap">{m.body}</div>
          </div>
        ))}
        {!rows.length && <p className="text-zinc-500 text-sm">No messages synced yet.</p>}
      </div>
    </div>
  );
}
