export interface Owner {
  id: string;
  display_name: string;
}

export interface SharedProps {
  auth: { owner: Owner | null };
  environment: { name: string; profile: string; localOnly: boolean };
  [key: string]: unknown;
}
