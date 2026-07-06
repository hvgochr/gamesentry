import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export type SelectOption = {
    value: string;
    label: string;
};

export type WatchedPlayerFormData = {
    game: string;
    routing_region: string;
    game_name: string;
    tag_line: string;
    discord_user_id: string;
    is_active: boolean;
};

type WatchedPlayerFormProps = {
    data: WatchedPlayerFormData;
    errors: Partial<Record<keyof WatchedPlayerFormData, string>>;
    gameOptions: SelectOption[];
    routingRegionOptions: SelectOption[];
    processing: boolean;
    submitLabel: string;
    disabled?: boolean;
    onChange: <TKey extends keyof WatchedPlayerFormData>(
        key: TKey,
        value: WatchedPlayerFormData[TKey],
    ) => void;
    onSubmit: (event: FormEvent<HTMLFormElement>) => void;
};

export default function WatchedPlayerForm({
    data,
    errors,
    gameOptions,
    routingRegionOptions,
    processing,
    submitLabel,
    disabled = false,
    onChange,
    onSubmit,
}: WatchedPlayerFormProps) {
    return (
        <form className="grid gap-4 lg:grid-cols-2" onSubmit={onSubmit}>
            <div className="grid gap-2">
                <Label htmlFor="game">Game</Label>
                <select
                    id="game"
                    className="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50"
                    value={data.game}
                    onChange={(event) => onChange('game', event.target.value)}
                    disabled={processing || disabled}
                >
                    {gameOptions.map((option) => (
                        <option key={option.value} value={option.value}>
                            {option.label}
                        </option>
                    ))}
                </select>
                <InputError message={errors.game} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="routing_region">Riot routing region</Label>
                <select
                    id="routing_region"
                    className="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50"
                    value={data.routing_region}
                    onChange={(event) =>
                        onChange('routing_region', event.target.value)
                    }
                    disabled={processing || disabled}
                >
                    {routingRegionOptions.map((option) => (
                        <option key={option.value} value={option.value}>
                            {option.label}
                        </option>
                    ))}
                </select>
                <InputError message={errors.routing_region} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="game_name">gameName</Label>
                <Input
                    id="game_name"
                    value={data.game_name}
                    onChange={(event) =>
                        onChange('game_name', event.target.value)
                    }
                    placeholder="SummonerName"
                    disabled={processing || disabled}
                />
                <InputError message={errors.game_name} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="tag_line">tagLine</Label>
                <Input
                    id="tag_line"
                    value={data.tag_line}
                    onChange={(event) =>
                        onChange('tag_line', event.target.value.toUpperCase())
                    }
                    placeholder="EUW"
                    className="uppercase"
                    disabled={processing || disabled}
                />
                <InputError message={errors.tag_line} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="discord_user_id">Discord user id</Label>
                <Input
                    id="discord_user_id"
                    value={data.discord_user_id}
                    onChange={(event) =>
                        onChange('discord_user_id', event.target.value)
                    }
                    placeholder="123456789012345678"
                    disabled={processing || disabled}
                />
                <InputError message={errors.discord_user_id} />
            </div>

            <div className="flex items-end">
                <label className="flex items-center gap-3 text-sm text-muted-foreground">
                    <Checkbox
                        checked={data.is_active}
                        onCheckedChange={(checked) =>
                            onChange('is_active', checked === true)
                        }
                        disabled={processing || disabled}
                    />
                    Active tracking
                </label>
            </div>

            <div className="lg:col-span-2">
                <Button type="submit" disabled={processing || disabled}>
                    {submitLabel}
                </Button>
            </div>
        </form>
    );
}
