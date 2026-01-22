import { ref, watch } from 'vue';
import confetti from 'canvas-confetti';
import { useAudio } from '@/Composables/useAudio';

export const useLevelUp = (
    profileRef,
    getLevel = (profile) => profile?.level_data?.current_level,
    options = {}
) => {
    const { playSfx } = useAudio();
    const showLevelUpModal = ref(false);
    const previousLevel = ref(getLevel(profileRef.value) || 1);
    const delayMs = Number.isFinite(options.delayMs) ? options.delayMs : 2500;

    const triggerLevelUpConfetti = () => {
        const duration = 3000;
        const end = Date.now() + duration;
        (function frame() {
            confetti({
                particleCount: 5,
                angle: 60,
                spread: 55,
                origin: { x: 0 },
                colors: ['#fbbf24', '#f59e0b', '#ef4444'],
            });
            confetti({
                particleCount: 5,
                angle: 120,
                spread: 55,
                origin: { x: 1 },
                colors: ['#3b82f6', '#8b5cf6', '#ec4899'],
            });
            if (Date.now() < end) requestAnimationFrame(frame);
        })();
    };

    watch(
        profileRef,
        (newProfile) => {
            if (!newProfile) return;
            const newLevel = getLevel(newProfile) || 1;
            if (newLevel > previousLevel.value) {
                const fire = () => {
                    showLevelUpModal.value = true;
                    triggerLevelUpConfetti();
                    playSfx('levelup');
                    previousLevel.value = newLevel;
                };
                if (delayMs > 0) {
                    setTimeout(fire, delayMs);
                } else {
                    fire();
                }
            }
        },
        { deep: true }
    );

    return { showLevelUpModal };
};
