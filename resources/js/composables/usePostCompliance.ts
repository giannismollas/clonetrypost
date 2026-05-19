import { computed, type ComputedRef, type Ref } from 'vue';
import { trans } from 'laravel-vue-i18n';

import { getMediaItemIssue, type MediaItem } from '@/composables/useMedia';
import { getMediaRulesForContentType } from '@/composables/useMediaRules';
import { getPlatformLabel } from '@/composables/usePlatformLogo';
import { ContentType } from '@/enums/content-type';
import { Platform } from '@/enums/platform';

export interface CompliancePostPlatform {
    id: string;
    platform: string;
    platform_name: string | null;
    social_account_id: string | null;
    content_type: string | null;
}

export interface CompliancePost {
    post_platforms: CompliancePostPlatform[];
}

export const PLATFORM_VARIANTS: Record<string, string[]> = {
    [Platform.Facebook]: [ContentType.FacebookPost, ContentType.FacebookReel, ContentType.FacebookStory],
    [Platform.Instagram]: [ContentType.InstagramFeed, ContentType.InstagramReel, ContentType.InstagramStory],
    [Platform.InstagramFacebook]: [ContentType.InstagramFeed, ContentType.InstagramReel, ContentType.InstagramStory],
    [Platform.LinkedIn]: [ContentType.LinkedInPost, ContentType.LinkedInCarousel],
    [Platform.LinkedInPage]: [ContentType.LinkedInPagePost, ContentType.LinkedInPageCarousel],
    [Platform.TikTok]: [ContentType.TikTokVideo, ContentType.TikTokPhoto],
    [Platform.Pinterest]: [ContentType.PinterestPin, ContentType.PinterestVideoPin, ContentType.PinterestCarousel],
};

export const getMediaIncompatibilityReason = (
    contentType: string,
    mediaItems: MediaItem[],
): string | null => {
    const rules = getMediaRulesForContentType(contentType);
    const videos = mediaItems.filter((m) => m.type === 'video' || m.mime_type?.startsWith('video/'));
    const images = mediaItems.filter((m) => m.type === 'image' || m.mime_type?.startsWith('image/'));
    const gifs = mediaItems.filter((m) => m.mime_type === 'image/gif');
    const total = mediaItems.length;

    if (rules.requiresMedia && total === 0) return trans('posts.edit.compliance.requires_media');
    if (!rules.acceptVideos && videos.length > 0) return trans('posts.edit.compliance.no_videos');
    if (!rules.acceptImages && images.length > 0) return trans('posts.edit.compliance.no_images');
    if (!rules.acceptsGif && gifs.length > 0) return trans('posts.edit.compliance.no_gifs');
    if (total > rules.maxFiles) return trans('posts.edit.compliance.too_many_files', { max: String(rules.maxFiles) });
    if (rules.minFiles && total < rules.minFiles) return trans('posts.edit.compliance.too_few_files', { min: String(rules.minFiles) });

    for (const m of mediaItems) {
        const isVideo = m.type === 'video' || m.mime_type?.startsWith('video/');
        const size = m.size ?? 0;
        const duration = m.meta?.duration ?? 0;
        const width = m.meta?.width ?? 0;
        const height = m.meta?.height ?? 0;

        if (isVideo) {
            if (rules.maxVideoBytes && size > 0 && size > rules.maxVideoBytes) return trans('posts.edit.compliance.video_too_large');
            if (rules.maxVideoDurationSec && duration > 0 && duration > rules.maxVideoDurationSec) {
                return trans('posts.edit.compliance.video_too_long', { seconds: String(rules.maxVideoDurationSec) });
            }
        } else if (rules.maxImageBytes && size > 0 && size > rules.maxImageBytes) {
            return trans('posts.edit.compliance.image_too_large');
        }

        if (width > 0 && height > 0 && (rules.aspectRatioMin || rules.aspectRatioMax)) {
            const ratio = width / height;
            if (rules.aspectRatioMin && ratio < rules.aspectRatioMin) return trans('posts.edit.compliance.aspect_ratio_invalid');
            if (rules.aspectRatioMax && ratio > rules.aspectRatioMax) return trans('posts.edit.compliance.aspect_ratio_invalid');
        }
    }

    return null;
};

export const firstCompatibleVariant = (
    platform: string,
    mediaItems: MediaItem[],
): string | null => {
    const variants = PLATFORM_VARIANTS[platform];
    if (!variants) return null;
    return variants.find((ct) => !getMediaIncompatibilityReason(ct, mediaItems)) ?? null;
};

interface UsePostComplianceOptions {
    post: ComputedRef<CompliancePost>;
    content: Ref<string>;
    media: Ref<MediaItem[]>;
    selectedPlatformIds: Ref<string[]>;
    platformContentTypes: Ref<Record<string, string>>;
    platformMeta: Ref<Record<string, Record<string, any>>>;
    platformConfigs: Record<string, { maxContentLength?: number | null }>;
}

export const usePostCompliance = (opts: UsePostComplianceOptions) => {
    const { post, content, media, selectedPlatformIds, platformContentTypes, platformMeta, platformConfigs } = opts;

    const platformLimits = computed(() => {
        const seen = new Set<string>();
        const result: { platform: string; maxLength: number }[] = [];
        for (const pp of post.value.post_platforms) {
            if (!selectedPlatformIds.value.includes(pp.id)) continue;
            if (seen.has(pp.platform)) continue;
            const accountId = pp.social_account_id;
            const max = accountId ? platformConfigs[accountId]?.maxContentLength : null;
            if (typeof max === 'number' && max > 0) {
                seen.add(pp.platform);
                result.push({ platform: pp.platform, maxLength: max });
            }
        }
        return result;
    });

    const mediaIssues = computed<Record<string, { platform: string; reason: string }[]>>(() => {
        const result: Record<string, { platform: string; reason: string }[]> = {};
        for (const item of media.value) {
            const issues: { platform: string; reason: string }[] = [];
            const seen = new Set<string>();
            for (const pp of post.value.post_platforms) {
                if (!selectedPlatformIds.value.includes(pp.id)) continue;
                if (seen.has(pp.platform)) continue;
                const contentType = platformContentTypes.value[pp.id] ?? pp.content_type ?? '';
                const reason = getMediaItemIssue(item, contentType);
                if (reason) {
                    seen.add(pp.platform);
                    issues.push({ platform: pp.platform, reason });
                }
            }
            if (issues.length > 0) result[item.id] = issues;
        }
        return result;
    });

    const platformIssues = computed<Record<string, string>>(() => {
        const issues: Record<string, string> = {};

        for (const pp of post.value.post_platforms) {
            const contentType = platformContentTypes.value[pp.id];
            if (!contentType) {
                issues[pp.id] = trans('posts.edit.compliance.no_content_type');
                continue;
            }

            const reason = getMediaIncompatibilityReason(contentType, media.value);
            if (!reason) continue;

            const isSelected = selectedPlatformIds.value.includes(pp.id);
            if (!isSelected && firstCompatibleVariant(pp.platform, media.value)) {
                continue;
            }

            issues[pp.id] = reason;
        }

        return issues;
    });

    const mediaCompliancePerPlatformValid = computed(
        () => selectedPlatformIds.value.every((id) => !platformIssues.value[id]),
    );

    // TikTok requires explicit privacy_level; when disclosure is on, at least
    // one brand sub-toggle (organic/content) must also be selected.
    const tiktokComplianceValid = computed(() => {
        const tiktokPlatforms = post.value.post_platforms.filter(
            (pp) => pp.platform === Platform.TikTok && selectedPlatformIds.value.includes(pp.id),
        );
        return tiktokPlatforms.every((pp) => {
            const meta = platformMeta.value[pp.id] ?? {};
            if (!meta.privacy_level) return false;
            if (meta.disclose && !meta.brand_organic_toggle && !meta.brand_content_toggle) return false;
            return true;
        });
    });

    const pinterestComplianceValid = computed(() => {
        const pinterestPlatforms = post.value.post_platforms.filter(
            (pp) => pp.platform === Platform.Pinterest && selectedPlatformIds.value.includes(pp.id),
        );
        return pinterestPlatforms.every((pp) => Boolean(platformMeta.value[pp.id]?.board_id));
    });

    const hasContentOrMedia = computed(
        () => content.value.trim().length > 0 || media.value.length > 0,
    );

    const contentLengthOverflows = computed(() => {
        const len = content.value.length;
        return platformLimits.value
            .filter((p) => len > p.maxLength)
            .map((p) => ({ platform: p.platform, limit: p.maxLength, over: len - p.maxLength }));
    });

    const canSchedule = computed(
        () => mediaCompliancePerPlatformValid.value
            && tiktokComplianceValid.value
            && pinterestComplianceValid.value
            && hasContentOrMedia.value
            && contentLengthOverflows.value.length === 0,
    );

    const postActionTooltip = computed(() => {
        if (canSchedule.value) return '';

        const mediaReasons = post.value.post_platforms
            .filter((pp) => selectedPlatformIds.value.includes(pp.id) && platformIssues.value[pp.id])
            .map((pp) => `${pp.platform_name ?? pp.platform}: ${platformIssues.value[pp.id]}`);

        const lengthReasons = contentLengthOverflows.value.map((overflow) => trans('posts.form.content_exceeds_platform', {
            platform: getPlatformLabel(overflow.platform),
            limit: String(overflow.limit),
            over: String(overflow.over),
        }));

        const reasons = [...mediaReasons, ...lengthReasons];
        if (reasons.length > 0) return reasons.join('\n');

        const tiktokDisclosureIncomplete = post.value.post_platforms.some((pp) => {
            if (pp.platform !== Platform.TikTok) return false;
            if (!selectedPlatformIds.value.includes(pp.id)) return false;
            const meta = platformMeta.value[pp.id] ?? {};
            return Boolean(meta.disclose) && !meta.brand_organic_toggle && !meta.brand_content_toggle;
        });

        if (tiktokDisclosureIncomplete) {
            return trans('posts.form.tiktok.compliance_incomplete');
        }

        if (!pinterestComplianceValid.value) {
            return trans('posts.form.pinterest.board_required');
        }

        if (!hasContentOrMedia.value) {
            return trans('posts.edit.compliance.requires_content_or_media');
        }

        return trans('posts.edit.compliance_incomplete');
    });

    return {
        platformLimits,
        mediaIssues,
        platformIssues,
        mediaCompliancePerPlatformValid,
        tiktokComplianceValid,
        pinterestComplianceValid,
        hasContentOrMedia,
        contentLengthOverflows,
        canSchedule,
        postActionTooltip,
    };
};
