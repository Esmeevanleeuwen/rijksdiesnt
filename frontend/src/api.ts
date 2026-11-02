export interface Provenance {
  name: string;
  url: string | null;
  external_id: string;
  retrieved_at: string;
  is_demo: boolean;
}

export interface Organization {
  id: string;
  official_name: string;
  display_name: string | null;
  organization_type: string | null;
  description: string | null;
  website_url: string | null;
  provenance: Provenance[];
}

interface ApiResponse<T> {
  data: T;
  meta: { total?: number; data_mode: string };
}

async function request<T>(path: string, signal: AbortSignal): Promise<T> {
  const response = await fetch(path, {
    signal,
    headers: { Accept: "application/json" },
  });
  if (!response.ok) {
    throw new Error(
      response.status === 404
        ? "Deze organisatie is niet gevonden."
        : "De gegevens konden niet worden geladen. Probeer het opnieuw.",
    );
  }
  return response.json() as Promise<T>;
}

export const getOrganizations = (query: string, signal: AbortSignal) =>
  request<ApiResponse<Organization[]>>(
    `/api/v1/organizations?q=${encodeURIComponent(query)}`,
    signal,
  );

export const getOrganization = (id: string, signal: AbortSignal) =>
  request<ApiResponse<Organization>>(
    `/api/v1/organizations/${encodeURIComponent(id)}`,
    signal,
  );

export function safeUrl(value: string | null): string | undefined {
  if (!value) return undefined;
  try {
    const url = new URL(value);
    return ["https:", "http:"].includes(url.protocol) ? url.href : undefined;
  } catch {
    return undefined;
  }
}
