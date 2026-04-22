function filterPlants() {
  const q = document.getElementById('searchInput').value.toLowerCase();
  document.querySelectorAll('.plant-item').forEach(el => {
    el.style.display = el.dataset.name.includes(q) ? '' : 'none';
  });
}
function openEditModal(p) {
  document.getElementById('edit_id').value         = p.id_plante;
  document.getElementById('edit_nom_commun').value = p.nom_commun;
  document.getElementById('edit_nom_sci').value    = p.nom_scientifique||'';
  document.getElementById('edit_desc').value       = p.description||'';
  document.getElementById('edit_tox').checked      = p.toxicite==1;
  setVal('edit_soin',p.niveau_soin); setVal('edit_arr',p.besoin_arrosage);
  setVal('edit_lum',p.besoin_luminosite); setVal('edit_hum',p.besoin_humidite);
  document.getElementById('editModal').style.display='flex';
}
function closeEditModal(){document.getElementById('editModal').style.display='none';}
function openCatalogModal(c){
  document.getElementById('cat_id').value   =c.id_catalogue;
  document.getElementById('cat_nom').value  =c.nom_commun;
  document.getElementById('cat_prix').value =c.prix;
  document.getElementById('cat_stock').value=c.stock;
  document.getElementById('cat_dispo').checked=c.disponible==1;
  document.getElementById('catalogModal').style.display='flex';
}
function closeCatalogModal(){document.getElementById('catalogModal').style.display='none';}
function setVal(id,val){const s=document.getElementById(id);if(s)for(let o of s.options)if(o.value===val){o.selected=true;break;}}
document.addEventListener('keydown',e=>{if(e.key==='Escape'){closeEditModal();closeCatalogModal();}});
