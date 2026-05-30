@if($newMajorAchievements->isNotEmpty())
<div class="modal fade" id="achievementModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width:380px;">
        <div class="modal-content border-0" style="border-radius:1.25rem;overflow:hidden;">
            <div class="modal-body text-center p-5">

                {{-- Icon --}}
                <div class="mb-4" id="achievementModalIcon">
                    <i class="bi bi-award" style="font-size:3rem; color:#f59e0b;"></i>
                </div>

                {{-- Label --}}
                <div class="text-uppercase text-muted fw-semibold mb-2" style="font-size:.7rem;letter-spacing:.12em;">
                    Achievement Unlocked
                </div>

                {{-- Title & description --}}
                <h5 class="fw-bold mb-2" id="achievementModalTitle" style="line-height:1.25;"></h5>
                <p class="text-muted small mb-0" id="achievementModalDesc" style="line-height:1.5;"></p>

                {{-- XP reward --}}
                <div class="mt-4">
                    <span class="badge fs-6 px-3 py-2" id="achievementModalXP"
                          style="background:rgba(67,94,190,.1);color:#435ebe;border-radius:.75rem;"></span>
                </div>

                {{-- CTA --}}
                <button type="button" class="btn btn-outline-secondary btn-sm mt-4 px-4"
                        id="achievementModalNext" style="border-radius:2rem;">
                    Lanjutkan
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var queue = @json($newMajorAchievements->values());
    var idx   = 0;

    var rarityIconMap = {
        gold:     { icon: 'bi-award',     color: '#f59e0b' },
        platinum: { icon: 'bi-gem',       color: '#8b5cf6' },
        silver:   { icon: 'bi-shield',    color: '#9e9e9e' },
        bronze:   { icon: 'bi-circle-fill', color: '#cd7f32' },
    };

    function fillModal(item) {
        var ach    = item.achievement;
        var rarity = ach.rarity || 'gold';
        var cfg    = rarityIconMap[rarity] || rarityIconMap.gold;

        document.getElementById('achievementModalIcon').innerHTML =
            '<i class="bi ' + cfg.icon + '" style="font-size:3rem;color:' + cfg.color + ';"></i>';
        document.getElementById('achievementModalTitle').textContent = ach.name;
        document.getElementById('achievementModalDesc').textContent  = ach.description;
        document.getElementById('achievementModalXP').textContent    = '+' + ach.xp_reward + ' XP';
    }

    function showNext() {
        if (idx >= queue.length) return;
        fillModal(queue[idx]);
        idx++;
        var modal = new bootstrap.Modal(document.getElementById('achievementModal'));
        modal.show();
    }

    document.getElementById('achievementModalNext').addEventListener('click', function () {
        bootstrap.Modal.getInstance(document.getElementById('achievementModal')).hide();
    });

    document.getElementById('achievementModal').addEventListener('hidden.bs.modal', showNext);

    document.addEventListener('DOMContentLoaded', function () {
        setTimeout(showNext, 600);
    });
}());
</script>
@endif
